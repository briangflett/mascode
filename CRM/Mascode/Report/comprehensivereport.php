<?php
/*
 +--------------------------------------------------------------------+
 | Comprehensive Donor Revenue Analysis Report                        |
 +--------------------------------------------------------------------+
 | Copyright JMA Consulting                                           |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * $Id$
 *
 */
class CRM_Report_Form_Contribute_ComprehensiveReport extends CRM_Report_Form {

  protected $_summary = NULL;

  protected $_customGroupExtends = array();

  protected $_dpoTempTable = 'civicrm_dpo_report_temp';

  protected $_contributionStatusId = NULL;

  protected $_sqlColumns = array();

  protected $_qParams = array();

  protected $_rowLabels = NULL;

  protected $_queryDates = NULL;
  /**
   */
  public function __construct() {
    $this->_columns = array(
      'civicrm_dpo' => array(
        'dao' => '',
        'fields' => array(
          'label' => array(
            'title' => ts(''),
            'required' => TRUE,
          ),
          'current_year' => array(
            'title' => ts('Current Year'),
            'required' => TRUE,
          ),
          'prior_year' => array(
            'title' => ts('Prior Year'),
            'required' => TRUE,
          ),
          'two_years_ago' => array(
            'title' => ts('Two Years Ago'),
            'required' => TRUE,
          ),
          'amount_difference' => array(
            'title' => ts('Amount Difference Current Year vs. Prior Year'),
            'required' => TRUE,
          ),
          'percentage_change' => array(
            'title' => ts('Percent Change Current Year vs. Prior Year'),
            'required' => TRUE,
          ),
        ),
        'filters' => array(
          'ending_date' => array(
            'operatorType' => CRM_Report_Form::OP_DATE,
            'title' => ts("Ending Date"),
          ),
        ),
      ),
    );
    $this->_contributionStatusId = CRM_Core_PseudoConstant::getKey(
      'CRM_Contribute_BAO_Contribution',
      'contribution_status_id',
      'Completed'
    );
    $this->_sqlColumns = array(
      'donor_number' => 'COUNT(DISTINCT cc.contact_id)',
      'revenue' => 'SUM(cc.total_amount)',
      'gift_number' => 'COUNT(DISTINCT cc.id)',
      'revenue_per_donor' => 'IF (COUNT(DISTINCT cc.contact_id) = 0, 0, SUM(cc.total_amount) / COUNT(DISTINCT cc.contact_id))',
      'gift_per_donor' => 'IF (COUNT(DISTINCT cc.contact_id) = 0, 0, COUNT(DISTINCT cc.id) / COUNT(DISTINCT cc.contact_id))',
      'revenue_per_gift' => 'IF (COUNT(DISTINCT cc.id) = 0, 0, SUM(cc.total_amount) / COUNT(DISTINCT cc.id))',
      'lost_last' => ' IF (SUM(contact_id_count) > 0 , SUM(contact_id_count), 0)',
      'donor_retention_rate' => 'IF (SUM(prior_year_donor) = 0, 0, SUM(retained_donor)/SUM(prior_year_donor) * 100)',
      'revenue_retention_rate' => 'IF (SUM(active_revenue) = 0, 0, SUM(retained_revenue)/SUM(active_revenue) * 100)',
      'attrition_rate' => 'IF (SUM(prior_year_donor) = 0, 0, (SUM(prior_year_donor) - SUM(retained_donor))/SUM(prior_year_donor) * 100)',
      'avg_donor_lifetime' => 'AVG(DATEDIFF(last_gift, first_gift)) / 365.25',
      'donor_lifetime_value' => ' SUM(lifetime) * SUM(amount)',
    );
    $this->_roundKeys = array(
      'revenue_per_donor',
      'revenue_per_gift',
      'donor_retention_rate',
      'revenue_retention_rate',
      'attrition_rate',
      'revenue',
      'gift_per_donor',
      'avg_donor_lifetime',
      'donor_lifetime_value',
    );
    $this->_prefixKeys = array(
      'revenue',
      'revenue_per_donor',
      'revenue_per_gift',
      'donor_lifetime_value',
    );
    $this->_suffixKeys = array(
      'donor_retention_rate',
      'revenue_retention_rate',
      'attrition_rate',
    );
    $this->_qParams = array(
      1 => array(
        2 => array('%2', 'Text'),
        3 => array(0, 'Integer'),
        4 => array(0, 'Integer'),
      ),
      2 => array(
        2 => array(0, 'Integer'),
        3 => array('%2', 'Text'),
        4 => array(0, 'Integer'),
      ),
      3 => array(
        2 => array(0, 'Integer'),
        3 => array(0, 'Integer'),
        4 => array('%2', 'Text'),
      ),
    );
    $this->getRowLabels();
    parent::__construct();
  }

  public function buildInstanceAndButtons() {
    parent::buildInstanceAndButtons();
    CRM_Core_Resources::singleton()->addScript(
    "CRM.$(function($) {
      $('.crm-absolute-date-to').hide();
      $('.crm-absolute-date-from label').text('Is:');
    });"
  );
  }

  protected function getQueryDates($date) {
    //$fiscalYear = Civi::settings()->get('fiscalYearStart');
    //$fiscalYear = date('Y') . '-' . implode('-', $fiscalYear);
    $fiscalYear = $nextYear = $date;
    $fiscalYear = strtotime($fiscalYear);

    $this->_currentYear = $date;
    $currentYear = $this->_lastYear = date('Y-m-d', strtotime('-1 year', $fiscalYear));
    $lastYear = $this->_twoYearsAgo = date('Y-m-d', strtotime('-2 year', $fiscalYear));
    $twoYearsAgo = date('Y-m-d', strtotime('-3 year', $fiscalYear));
    $threeYearsAgo = date('Y-m-d', strtotime('-4 year', $fiscalYear));
    $fourYearsAgo = date('Y-m-d', strtotime('-5 year', $fiscalYear));
    $this->_queryDates = array(
      1 => array(
        8 => array($nextYear, 'String'),
        9 => array($currentYear, 'String'),
        10 => array($lastYear, 'String'),
        11 => array($twoYearsAgo, 'String'),
      ),
      2 => array(
        8 => array($currentYear, 'String'),
        9 => array($lastYear, 'String'),
        10 => array($twoYearsAgo, 'String'),
        11 => array($threeYearsAgo, 'String'),
      ),
      3 => array(
        8 => array($lastYear, 'String'),
        9 => array($twoYearsAgo, 'String'),
        10 => array($threeYearsAgo, 'String'),
        11 => array($fourYearsAgo, 'String'),
      ),
    );
  }

  protected function getRowLabels() {
    $this->_rowLabels = array(
      'activeDonors' => array(
        ts('Number of Active Donors') => array('donor_number'),
        ts('Total Revenue') => array('revenue'),
        ts('Number of Gifts') => array('gift_number'),
        ts('Revenue Per Donor (Year to Date)') => array('revenue_per_donor'),
        ts('Revenue Per Gift (Average Gift)') => array('revenue_per_gift'),
        ts('Gifts Per Donor') => array('gift_per_donor'),
        ts('Number of 2+ givers for year') => array('donor_number', 'two_or_more_gifts'),
      ),
      'retainedDonor' => array(
        ts('Number of Retained Donors') => array('donor_number'),
        ts('Donor Retention Rate') => array('donor_retention_rate', 'donor_retention_rate'),
        ts('Retained Donor Revenue') => array('revenue'),
        ts('Revenue Retention Rate') => array('revenue_retention_rate', 'revenue_retention_rate'),
        ts('Revenue per Retained Donor') => array('revenue_per_donor'),
        ts('Average Donor Lifetime(TBD)') => array('avg_donor_lifetime', 'avg_donor_lifetime'),
        ts('Lifetime Donor Value(TBD)') => array('donor_lifetime_value', 'donor_lifetime_value'),
      ),
      'newDonor' => array(
        ts('Number of New Donors') => array('donor_number'),
        ts('New Donor Revenue') => array('revenue'),
        ts('Number of New Donor Gifts') => array('gift_number'),
        ts('Revenue per New Donor') => array('revenue_per_donor'),
        ts('Revenue per New Donor Gift') => array('revenue_per_gift'),
        ts('Gifts per New Donor') => array('gift_per_donor'),
      ),
      'reactivatedDonor' => array(
        ts('Reactivated Donors') => array('donor_number'),
        ts('Reactivated Donor Revenue') => array('revenue'),
        ts('Number of Reactivated Gifts') => array('gift_number'),
        ts('Revenue per Reactivated Donor') => array('revenue_per_donor'),
        ts('Revenue per Reactivated Gift') => array('revenue_per_gift'),
        ts('Gifts per Reactivated Donor') => array('gift_per_donor'),
      ),
      'attrition' => array(
        ts('Attrition Rate') => array('attrition_rate', 'attrition_rate'),
        ts('Number of Donors Active Last 2 years') => array('donor_number', 'active_2'),
        ts('Number of Donors Active Last 3+ years') => array('donor_number', 'active_3'),
        ts('Number of Donors lost from Last Year') => array('lost_last', 'lost_last'),
        ts('Number of 2yr Donors lost from Last Year') => array('donor_number', 'lost_2'),
      )
    );
  }

  public function from() {
    $date = date('Y-m-d');
    if (!empty($this->_submitValues['ending_date_relative'])) {
      $dates = $this->getFromTo($this->_submitValues['ending_date_relative']);
      $date = date('Y-m-d', strtotime($dates[0]));
    }
    elseif (!empty($this->_submitValues['ending_date_from'])) {
      $date = date('Y-m-d', strtotime($this->_submitValues['ending_date_from']));
    }
    $this->getQueryDates($date);
    if ($date) {
      $this->_columnHeaders['civicrm_dpo_current_year']['title'] = ts('Current Year ' . date('m/d/Y', strtotime($this->_currentYear)));
      $this->_columnHeaders['civicrm_dpo_prior_year']['title'] = ts('Prior Year ' . date('m/d/Y', strtotime($this->_lastYear)));
      $this->_columnHeaders['civicrm_dpo_two_years_ago']['title'] = ts('Two Years Ago ' . date('m/d/Y', strtotime($this->_twoYearsAgo)));
    }
    $this->buildTempTableForDPOReport();
    $this->_from .= " FROM {$this->_dpoTempTable} {$this->_aliases['civicrm_dpo']}";
  }

  protected function buildTempTableForDPOReport() {
    CRM_Core_DAO::executeQuery("DROP TEMPORARY TABLE IF EXISTS {$this->_dpoTempTable}");
    $tempQuery = "CREATE TEMPORARY TABLE {$this->_dpoTempTable} (
      `label` VARCHAR(255) DEFAULT NULL,
      `current_year` VARCHAR(255) DEFAULT NULL,
      `prior_year` VARCHAR(255) DEFAULT NULL,
      `two_years_ago` VARCHAR(255) DEFAULT NULL,
      `amount_difference` VARCHAR(255) DEFAULT NULL,
      `percentage_change` VARCHAR(255) DEFAULT NULL
    )";
    CRM_Core_DAO::executeQuery($tempQuery);
    $this->insertActiveDonor();
    $this->insertRetainedDonor();
    $this->insertNewDonor();
    $this->insertReactivatedDonor();
    $this->insertAttrition();
  }

  protected function insertActiveDonor() {
    $queries = array(
      'common_query' => " SELECT %1 label, %2 current_year, %3 prior_year, %4 two_years_ago
        FROM civicrm_contribution cc
        WHERE cc.contribution_status_id IN (%5) AND cc.receive_date >= %9 AND cc.receive_date <= %8",
      'two_or_more_gifts' => "SELECT %1 label, %2 current_year, %3 prior_year, %4 two_years_ago FROM (
          SELECT cc.contact_id
          FROM civicrm_contribution cc
          WHERE cc.contribution_status_id IN (%5) AND cc.receive_date >= %9 AND cc.receive_date <= %8
          GROUP BY contact_id HAVING COUNT(id) > 1
        ) AS cc
      ",
    );
    $this->processQueries(
      ts('Active Donors'),
      $this->_rowLabels['activeDonors'],
      $queries
    );
  }

  protected function insertRetainedDonor() {
    $queries = array(
      'common_query' => " SELECT %1 label, %2 current_year, %3 prior_year, %4 two_years_ago
      FROM civicrm_contribution cc
      WHERE  cc.contribution_status_id IN (%5)
        AND (cc.receive_date >= %9 AND cc.receive_date <= %8)
        AND cc.contact_id IN (
          SELECT contact_id FROM civicrm_contribution
            WHERE receive_date <= %9 AND receive_date >= %10
            AND contribution_status_id IN (%5)
            GROUP BY contact_id
        )
      ",
      'donor_retention_rate' => "SELECT %1 label, %2 current_year, %3 prior_year, %4 two_years_ago
        FROM (
          SELECT COUNT(DISTINCT contact_id) AS retained_donor, 0 as prior_year_donor
            FROM civicrm_contribution cc
            WHERE  cc.contribution_status_id IN (%5)
              AND (cc.receive_date >= %9 AND cc.receive_date <= %8)
              AND cc.contact_id IN (
                SELECT contact_id FROM civicrm_contribution
                  WHERE receive_date <= %9 AND receive_date >= %10
                  AND contribution_status_id IN (%5)
                  GROUP BY contact_id
                )
                UNION
                SELECT 0, COUNT(DISTINCT contact_id)
                FROM civicrm_contribution cc
                WHERE cc.contribution_status_id IN (%5)
                  AND cc.receive_date >= %10 AND cc.receive_date <= %9
      ) temp",
      'revenue_retention_rate' => "SELECT %1 label, %2 current_year, %3 prior_year, %4 two_years_ago
        FROM (
          SELECT SUM(total_amount) AS retained_revenue, 0 as active_revenue
            FROM civicrm_contribution cc
            WHERE  cc.contribution_status_id IN (%5)
              AND (cc.receive_date >= %9 AND cc.receive_date <= %8)
              AND cc.contact_id IN (
                SELECT contact_id FROM civicrm_contribution
                  WHERE receive_date <= %9 AND receive_date >= %10
                    AND contribution_status_id IN (%5)
                  GROUP BY contact_id
                )
                UNION
                SELECT 0, SUM(total_amount)
                FROM civicrm_contribution cc
                WHERE cc.contribution_status_id IN (%5)
                  AND cc.receive_date >= %9 AND cc.receive_date <= %8
      ) temp",
      'avg_donor_lifetime' => "SELECT %1 label, %2 current_year, %3 prior_year, %4 two_years_ago
        FROM (
          SELECT MIN(receive_date) AS first_gift, MAX(receive_date) AS last_gift
            FROM civicrm_contribution cc
            WHERE cc.contribution_status_id IN (%5) AND cc.total_amount > 0
              AND cc.contact_id IN (
                SELECT DISTINCT contact_id
                FROM civicrm_contribution cc
                WHERE cc.contribution_status_id IN (%5)
                  AND (cc.receive_date >= %9 AND cc.receive_date <= %8)
                  AND cc.total_amount > 0
                  AND cc.contact_id IN (
                    SELECT contact_id FROM civicrm_contribution
                    WHERE receive_date <= %9 AND receive_date >= %10
                      AND contribution_status_id IN (%5) AND total_amount > 0
                    GROUP BY contact_id
                  )
              ) GROUP BY cc.contact_id
        ) AS temp
      ",
      'donor_lifetime_value' => "SELECT %1 label, %2 current_year, %3 prior_year, %4 two_years_ago
        FROM (
          SELECT AVG(DATEDIFF(last_gift,first_gift))/365.25 AS lifetime, 0 AS amount
            FROM (
              SELECT MIN(receive_date) AS first_gift, MAX(receive_date) AS last_gift
              FROM civicrm_contribution cc
              WHERE cc.contribution_status_id IN (%5) AND cc.total_amount > 0
                AND cc.contact_id IN (
                  SELECT DISTINCT contact_id
                  FROM civicrm_contribution cc
                  WHERE cc.contribution_status_id IN (%5)
                    AND (cc.receive_date >= %9 AND cc.receive_date <= %8)
                    AND cc.total_amount > 0
                    AND cc.contact_id IN (
                      SELECT contact_id FROM civicrm_contribution
                      WHERE receive_date <= %9 AND receive_date >= %10
                        AND contribution_status_id IN (%5) AND total_amount > 0
                      GROUP BY contact_id
                    )
                ) GROUP BY cc.contact_id
            ) AS S1
          UNION
          SELECT 0, SUM(total_amount)/COUNT(DISTINCT contact_id) as amount
            FROM civicrm_contribution cc
              WHERE cc.contribution_status_id IN (%5) AND cc.receive_date >= %9
                AND cc.receive_date <= %8
          ) AS temp"
    );
    $this->processQueries(
      ts('Retained Donors'),
      $this->_rowLabels['retainedDonor'],
      $queries
    );
  }

  protected function insertNewDonor() {
    $queries = array(
      'common_query' => " SELECT %1 label, %2 current_year, %3 prior_year, %4 two_years_ago
        FROM civicrm_contribution cc
          LEFT JOIN civicrm_contribution cc1 ON cc.contact_id = cc1.contact_id
            AND cc1.receive_date <= %9
        WHERE cc1.id IS NULL AND cc.contribution_status_id IN (%5) AND cc.receive_date <= %8
      ",
    );
    $this->processQueries(
      ts('New Donors'),
      $this->_rowLabels['newDonor'],
      $queries
    );
  }

  protected function insertReactivatedDonor() {
    $queries = array(
      'common_query' => " SELECT %1 label, %2 current_year, %3 prior_year, %4 two_years_ago
      FROM civicrm_contribution cc
      WHERE cc.contribution_status_id IN (%5)
        AND cc.receive_date >= %9 AND cc.receive_date <= %8
        AND cc.contact_id IN (
          SELECT contact_id FROM civicrm_contribution
            WHERE receive_date <= %10
             AND contribution_status_id IN (%5)
            GROUP BY contact_id
        )
        AND cc.contact_id NOT IN (
          SELECT contact_id FROM civicrm_contribution
          WHERE receive_date <= %9 AND receive_date >= %10
           AND contribution_status_id IN (%5)
          GROUP BY contact_id
        )
      ",
    );
    $this->processQueries(
      ts('Reactivated Donors'),
      $this->_rowLabels['reactivatedDonor'],
      $queries
    );
  }

  protected function insertAttrition() {
    $queries = array(
      'common_query' => " SELECT %1 label, %2 current_year, %3 prior_year, %4 two_years_ago
        FROM civicrm_contribution cc
        WHERE 0
      ",
      'active_2' => "SELECT %1 label, %2 current_year, %3 prior_year, %4 two_years_ago
        FROM civicrm_contribution cc
          WHERE cc.contribution_status_id IN (%5)
          AND (cc.receive_date >= %9 AND cc.receive_date <= %8)
          AND cc.contact_id IN (
            SELECT contact_id FROM civicrm_contribution
              WHERE receive_date <= %9 AND receive_date >= %10
                AND contribution_status_id IN (%5)
              GROUP BY contact_id
            )
      ",
      'active_3' => "SELECT %1 label, %2 current_year, %3 prior_year, %4 two_years_ago
        FROM civicrm_contribution cc
          WHERE cc.contribution_status_id IN (%5)
          AND (cc.receive_date >= %9 AND cc.receive_date <= %8)
          AND cc.contact_id IN (
            SELECT contact_id FROM civicrm_contribution
              WHERE receive_date <= %9 AND receive_date >= %10
                AND contribution_status_id IN (%5)
                AND contact_id IN ( SELECT contact_id FROM civicrm_contribution
              WHERE receive_date <= %10 AND receive_date >= %11
                AND contribution_status_id IN (%5)
              GROUP BY contact_id)
              GROUP BY contact_id
            )
      ",
      'lost_last' => "SELECT %1 label, %2 current_year, %3 prior_year, %4 two_years_ago
        FROM (
          SELECT  1, -COUNT(DISTINCT contact_id)  AS contact_id_count
                FROM civicrm_contribution cc
                WHERE  cc.contribution_status_id IN (%5)
                AND (cc.receive_date >= %9 AND cc.receive_date <= %8)
                AND cc.contact_id 	IN (
                  SELECT contact_id FROM civicrm_contribution
                    WHERE receive_date <= %9 AND receive_date >= %10
                      AND contribution_status_id IN (%5)
                    GROUP BY contact_id
                  )
                UNION
                SELECT 2, COUNT(DISTINCT contact_id)
                FROM civicrm_contribution cc
                WHERE cc.contribution_status_id IN (%5)
                  AND cc.receive_date >= %10 AND cc.receive_date <= %9
      ) temp
      ",
      'attrition_rate' => "SELECT %1 label, %2 current_year, %3 prior_year, %4 two_years_ago
        FROM (
          SELECT COUNT(DISTINCT contact_id) AS retained_donor, 0 as prior_year_donor
            FROM civicrm_contribution cc
            WHERE  cc.contribution_status_id IN (%5)
              AND (cc.receive_date >= %9 AND cc.receive_date <= %8)
              AND cc.contact_id IN (
                SELECT contact_id FROM civicrm_contribution
                  WHERE receive_date <= %9 AND receive_date >= %10
                    AND contribution_status_id IN (%5)
                  GROUP BY contact_id
                )
                UNION
                SELECT 0, COUNT(DISTINCT contact_id) prior_year_donor
                FROM civicrm_contribution cc
                WHERE cc.contribution_status_id IN (%5)
                  AND cc.receive_date >= %10 AND cc.receive_date <= %9
      ) temp",
      'lost_2' => " SELECT %1 label, %2 current_year, %3 prior_year, %4 two_years_ago
        FROM civicrm_contribution cc
        WHERE cc.receive_date >= %10 AND cc.receive_date <= %9
          AND  contribution_status_id IN (%5)
          AND contact_id IN (
            SELECT DISTINCT contact_id
              FROM civicrm_contribution cc
              WHERE cc.receive_date >= %11 AND cc.receive_date <= %10
                AND  contribution_status_id IN (%5))
                AND contact_id NOT IN (SELECT DISTINCT contact_id
                    FROM civicrm_contribution cc
                    WHERE cc.receive_date >= %9 AND cc.receive_date <= %8
                      AND  contribution_status_id IN (%5))
      ",
    );
    $this->processQueries(
      ts('Attrition'),
      $this->_rowLabels['attrition'],
      $queries
    );
  }

  protected function processQueries($section, $labels, $queries) {
    $sql = " INSERT INTO {$this->_dpoTempTable} ";
    if ($section) {
      $query = $sql . "(label) VALUES ('<b>{$section}</b>')";
      CRM_Core_DAO::disableFullGroupByMode();
      CRM_Core_DAO::executeQuery($query);
      CRM_Core_DAO::reenableFullGroupByMode();
    }
    $mainQuery = $this->getQuery($queries['common_query']);
    foreach ($labels as $key => $values) {
      $round = 0;
      if (in_array($values[0], $this->_roundKeys)) {
        $round = 2;
      }
      $prefix = '';
      if (in_array($values[0], $this->_prefixKeys)) {
        $prefix = CRM_Core_Config::singleton()->defaultCurrencySymbol;
      }
      $suffix = '';
      if (in_array($values[0], $this->_suffixKeys)) {
        $suffix = '%';
      }
      $query = (empty($values[1]) ? $mainQuery : $this->getQuery($queries[$values[1]]));
      $query = "SELECT label,
          CONCAT('{$prefix}', FORMAT(ROUND(SUM(current_year), {$round}), {$round}), '{$suffix}'),
          CONCAT('{$prefix}', FORMAT(ROUND(SUM(prior_year), {$round}), {$round}), '{$suffix}'),
          CONCAT('{$prefix}', FORMAT(ROUND(SUM(two_years_ago), {$round}), {$round}), '{$suffix}'),
          CONCAT('{$prefix}', FORMAT(ROUND((SUM(current_year) - SUM(prior_year)), {$round}), {$round}), '{$suffix}'),
          CONCAT(FORMAT(ROUND(((SUM(current_year) - SUM(prior_year))/SUM(prior_year) * 100), 2), 2), '%')
        FROM ({$query}) AS temp"
      ;
      $query = $sql . $query;
      $params = array(
        1 => array($key, 'String'),
        2 => array($this->_sqlColumns[$values[0]], 'Text'),
        5 => array($this->_contributionStatusId, 'String'),
      );
      CRM_Core_DAO::disableFullGroupByMode();
      CRM_Core_DAO::executeQuery($query, $params);
      CRM_Core_DAO::reenableFullGroupByMode();
    }
  }

  protected function getQuery($query) {
    $queries = array();
    for ($i = 1; $i < 4; $i++) {
      $params = $this->_queryDates[$i] + $this->_qParams[$i];
      $queries[] = CRM_Core_DAO::composeQuery($query, $params);
    }
    //CRM_Core_Error::debug('sss', $queries);
    return implode(' UNION ', $queries);
  }

}
