# RCS chase backlog — the 39 unarmed Service Requests

**Status: a procedure, not an executed action.** Nothing in this document has
been run. No email has been sent. Deciding what reaches these clients is the
coordinator's call with Brian, not a script's.

Measured against production (read-only) on **2026-09-09**.

## What this is

`mas_lifecycle_rcs_chase` arms on a status *transition* into "Request RCS".
Until `upgrade_5012` the only intake path that produced one was the web form, so
Service Requests created directly at "Request RCS" in the CiviCRM "New Case" UI
were never chased. `mas_lifecycle_rcs_chase_on_create` fixes that **for new
cases only** — it fires on case creation, so it can never reach a case that
already exists.

That leaves 39 Service Requests sitting in "Request RCS" that were never armed
and never chased. This is the list, the buckets, and the procedure if any of them
are to be chased.

## Why it is not a script

Three reasons, all of which survived checking:

1. **Age.** Nine of the 39 have been silent for 10–23 months. Chasing a client
   about a request from October 2024 is worse than silence.
2. **The 23-hour dedupe does not protect a sweep.** `LifecycleMailer::findDuplicate()`
   is scoped to one case + one template, so it throttles a repeat send to the
   *same* case. Across 39 distinct cases it throttles nothing — all 39 would
   send.
3. **Five have nobody to send to.** No active Case Client Rep relationship, so
   `LifecycleEmail::processAction()` would resolve no recipient and log a warning.

## The buckets

| bucket | n | case IDs |
|---|---|---|
| 2024 | 3 | 13287, 17818, 17843 |
| 2025 | 6 | 18200, 18229, 18249, 18292, 18451, 18507 |
| 2026, entered before the rule existed (2026-06-11) | 20 | 18642, 18649, 18655, 18658, 18662, 18663, 18676, 18680, 18683, 18695, 18698, 18711, 18713, 18723, 18725, 18737, 18738, 18742, 18760, 18776 |
| **2026, after the rule existed — the live leak** | **10** | 18799, 18800, 18803, 18806, 18818, 18826, 18836, 18842, 18846, 18852 |

**No active client rep** (send would resolve no recipient): 18200, 18229, 18249,
18292, 18451 — all in the 2025 bucket.

**Oldest to newest**, days open as at 2026-09-09: 13287 (700), 17818 (678),
17843 (658), 18200 (510), 18229 (489), 18249 (474), 18292 (439), 18451 (331),
18507 (302), 18642 (177), 18649 (169), 18655 (168), 18658 (166), 18662 (161),
18663 (161), 18676 (150), 18680 (147), 18683 (147), 18695 (142), 18698 (141),
18711 (131), 18713 (128), 18723 (119), 18725 (119), 18737 (110), 18738 (109),
18742 (106), 18760 (100), 18776 (92), 18799 (72), 18800 (72), 18803 (70),
18806 (69), 18818 (49), 18826 (35), 18836 (15), 18842 (12), 18846 (9),
18852 (8).

## Recommendation, for Brian and the coordinator to decide

| bucket | recommendation |
|---|---|
| 2024 + 2025 (9) | **Do not chase.** Triage to "No Client Response" or "Cancelled" as a data-hygiene pass. |
| 2026 pre-rule (20, avg 137 days) | **Review as a list first.** Most are probably stale; a chase at 4+ months reads oddly. |
| 2026 post-rule (10, avg 41 days) | **The legitimate candidates.** Chase in small batches. |

The 2026 post-rule ten are the ones the defect actually cost — they would have
been chased automatically had they arrived through the web form.

## The procedure, if a batch is to be chased

**Do it by driving a real status transition, one case at a time.** Never by
calling `LifecycleMailer` directly per case.

Why the distinction matters, and it is the single most important thing on this
page: `CRM_Civirules_Engine` computes the delay from `new DateTime()` at trigger
time, so a re-armed case queues its chases at **+21 and +42 days from today**,
not immediately and not backdated. Calling the mailer directly bypasses the
delay and sends at once — that is the "months of backdated chases all at once"
scenario, and it only arises from the shortcut.

For each case in the approved batch:

```
1. Confirm the case is still at "Request RCS" and still has an active
   Case Client Rep. If either is false, skip it and say so.
2. Move the case to "Ongoing"      (status_id:name = 'Open')
3. Move the case to "Request RCS"  (status_id:name = 'Request RCS')
```

Step 2 is inert: `mas_lifecycle_rcs_chase` requires the *new* value to be
"Request RCS", which that move is not, and no other rule is on
service_request + `changed_case`. Step 3 is the arming transition.

**This two-step is verified working** — measured on dev 2026-09-09, a case at
"Request RCS" taken down to "Ongoing" and back armed the chase (rule log 0 → 0 → 1).

**It must be run outside any enclosing database transaction.** This is not
incidental. When a transaction is open, CiviRules defers its post triggers to
`PHASE_POST_COMMIT` while `CRM_Civirules_Utils_PreData::pre()` still runs inline
and overwrites the stored "original" status per update — so the two-step arms
*nothing*. Measured: 0 firings from inside case creation, 1 from outside. A
remediation script must therefore do its status writes at the top level of
`cv scr`, not wrapped in a transaction.

After the batch, confirm each case armed:

```sql
SELECT entity_id AS case_id, COUNT(*) AS firings
  FROM civirule_rule_log
 WHERE rule_id = (SELECT id FROM civirule_rule WHERE name = 'mas_lifecycle_rcs_chase')
   AND entity_table = 'civicrm_case'
   AND entity_id IN (<batch>)
 GROUP BY entity_id;
```

Expect a non-zero count per case (2 per entry is the production norm — the
`changed_case` trigger fires once per case client and once per case role; the
duplicate *send* is absorbed by the 23-hour dedupe, which is why fully-chased
cases show two "Sent Automated Email" activities and not four).

## Known gap this makes more visible

Fixing the arming adds cases to a pipeline that has **no exit**. See BACKLOG.md,
"RCS chase cadence has no terminal step".

---

*Compiled 2026-09-09 from read-only production inspection. Case counts and ages
are as at that date and will drift.*
