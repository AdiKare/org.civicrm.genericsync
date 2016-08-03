{* HEADER *}
<div class="crm-block crm-form-block crm-generic-genericsync-form-block" >

	<!-- {if $smarty.get.state eq 'done'}
    <div class="help">
      {ts}Import completed with result counts as:{/ts}<br/>
      {foreach from=$stats item=group}
      <h2>{$group.name}</h2>
      <table class="form-layout-compressed bold">
      <tr><td>{ts}Contacts on Mailchimp{/ts}:</td><td>{$group.stats.mc_count}</td></tr>
      <tr><td>{ts}Contacts on CiviCRM (originally){/ts}:</td><td>{$group.stats.c_count}</td></tr>
      <tr><td>{ts}Contacts that were in sync already{/ts}:</td><td>{$group.stats.in_sync}</td></tr>
      <tr><td>{ts}Contacts Added to the CiviCRM group{/ts}:</td><td>{$group.stats.added}</td></tr>
      <tr><td>{ts}Contacts Removed from the CiviCRM group{/ts}:</td><td>{$group.stats.removed}</td></tr>
      </table>
      {/foreach}
    </div>
  	{/if} -->
  

	<div class="crm-accordion-header">
      <div class="icon crm-accordion-pointer"></div> 
      {ts}SYCNING OPTIONS{/ts}
    </div><!-- /.crm-accordion-header -->
	<div class="crm-accordion-body">

		<table class="form-layout-compressed">
			<tr class="crm-generic-genericsync-service-block">
          		<td class="label">{$form.service.label}</td>
				<td>{$form.service.html}<br/>
				</td>
			</tr>
			<tr class = "crm-generc-genericsync-direction-block">
				<td class="label">{$form.direction.label}</td>
				<td>{$form.direction.html}<br/>
				</td>
			</tr>
		</table>
	</div>
    <div class="crm-submit-buttons">
      {include file="CRM/common/formButtons.tpl" location="top"}
    </div>
</div>
