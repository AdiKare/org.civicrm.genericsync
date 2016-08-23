{* HEADER *}
<div class="crm-block crm-form-block crm-generic-genericsync-form-block" >
 

	<div class="crm-accordion-header">
      <div class="icon crm-accordion-pointer"></div> 
      {ts}SYNCING OPTIONS{/ts}
    </div><!-- /.crm-accordion-header -->
	<div class="crm-accordion-body">

		<table class="form-layout-compressed">
			<tr class="crm-generic-genericsync-service-block">
          		<td class="label">{$form.service.label}</td>
				<td>{$form.service.html}<br/>
				</td>
			</tr>
			
		</table>
	</div>
    <div class="crm-submit-buttons">
      {include file="CRM/common/formButtons.tpl" location="top"}
    </div>
</div>
