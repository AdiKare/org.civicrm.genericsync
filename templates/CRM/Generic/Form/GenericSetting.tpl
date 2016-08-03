<div class="crm-block crm-form-block crm-generic-genericsettings-form-block" >

  <div class="crm-accordion-header">
      <div class="icon crm-accordion-pointer"></div> 
      {ts}SETTINGS OPTIONS{/ts}
    </div><!-- /.crm-accordion-header -->
  <div class="crm-accordion-body">

    <table class="form-layout-compressed">
      <tr class="crm-generic-genericsettings-service-block">
              <td class="label">{$form.service.label}</td>
        <td>{$form.service.html}<br/>
        </td>
      </tr>
    </table>
    <!-- <div class="crm-submit-buttons">
      {include file="CRM/common/formButtons.tpl" location="top"}
    </div> -->
    <input type="button" value="Save" id="save"/>
</div>

{literal}

<script type="text/javascript" >
  
  cj("#save").click(function(){
    var sel = cj("div[class= 'crm-generic-genericsettings-service-block'] input[type='radio']:checked").val();
    alert(sel) ;
  });
  

</script>
{/literal}
