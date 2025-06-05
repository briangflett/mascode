{* templates/CRM/Mascode/CiviRules/Form/EmployerRelationship.tpl *}

<h3>{ts}Add Relationship to Employer Configuration{/ts}</h3>

<div class="crm-block crm-form-block crm-form-block-civirules-action-employer-relationship">
    
    <div class="help">
        <p>{ts}This action will create a relationship between a contact and their employer organization. The relationship will be created when the rule triggers.{/ts}</p>
        <p><strong>{ts}Note:{/ts}</strong> {ts}The contact must have an employer assigned for this action to work.{/ts}</p>
    </div>

    <div class="crm-section">
        <div class="label">{$form.relationship_type_id.label}</div>
        <div class="content">
            {$form.relationship_type_id.html}
            <div class="description">{ts}Select the type of relationship to create between the individual and their employer organization.{/ts}</div>
        </div>
        <div class="clear"></div>
    </div>

</div>

<div class="crm-submit-buttons">
    {include file="CRM/common/formButtons.tpl" location="bottom"}
</div>