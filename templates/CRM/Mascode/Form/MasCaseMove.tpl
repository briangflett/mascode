{* Template for Move Cases Between Organizations form *}
<div class="crm-form-block crm-block crm-mascode-case-move-form-block">

  <div class="help">
    <p>{ts}Use this form to move all cases from one organization to another. This is useful when organizations merge or when cases were incorrectly assigned.{/ts}</p>
  </div>

  <table class="form-layout-compressed">
    <tr>
      <td class="label">{$form.from_organization_id.label}</td>
      <td>{$form.from_organization_id.html}
        <div class="description">{ts}Select the organization to move cases FROM{/ts}</div>
      </td>
    </tr>
    <tr>
      <td class="label">{$form.to_organization_id.label}</td>
      <td>{$form.to_organization_id.html}
        <div class="description">{ts}Select the organization to move cases TO{/ts}</div>
      </td>
    </tr>
  </table>

  <div class="messages warning">
    <div class="icon alert-icon"></div>
    <p><strong>{ts}Warning:{/ts}</strong> {ts}This action will transfer ALL cases from the source organization to the target organization. Case Client Rep and Case Coordinator relationships will be preserved. This action cannot be undone.{/ts}</p>
  </div>

</div>

{* Include the form buttons *}
<div class="crm-submit-buttons">
{include file="CRM/common/formButtons.tpl" location="bottom"}
</div>