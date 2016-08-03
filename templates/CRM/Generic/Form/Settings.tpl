<div class="crm-block crm-form-block crm-generic-genericsettings-form-block" >

  <div class="crm-accordion-header">
      <div class="icon crm-accordion-pointer"></div> 
      {ts}SERVICE SETTINGS OPTIONS{/ts}
    </div><!-- /.crm-accordion-header -->
  <div class="crm-accordion-body">

    <table class="form-layout-compressed">
      <tr class="crm-generic-genericsettings-service-block">
              <td class="label">{$form.service.label}</td>
        <td>{$form.service.html}<br/>
        </td>
      </tr>
    </table>
    <!-- <div id= "servicesettings">
    &nbsp;&nbsp;&nbsp;&nbsp;Service:&nbsp;&nbsp; 
    <input type= "radio" value="Mailchimp">Mailchimp</input>
      <input type= "radio" value="Constant">Constant</input>
        <input type= "radio" value="Google">Google</input> -->
  </div>
    <div class="crm-submit-buttons">
      {include file="CRM/common/formButtons.tpl" location="top"}
    </div>
    <!-- <input type="button" value="Save" id="save"/> -->
    
</div>


<!-- {literal}

<script type="text/javascript" >
  cj("#save").click(function(){
    var sel = cj("#servicesettings input[type='radio']:checked").val();
    jQuery.ajax({
      type: "POST", 
      url: CRM.url('civicrm/generic/genericsettings/selectService'),
      data: 'service='+sel,
    }).done(function(data){
      alert(data);
    });
  });


</script>
{/literal}
 -->