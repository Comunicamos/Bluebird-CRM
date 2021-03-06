{literal}
<style type="text/css">
  #output {
    border: 1px solid orange;
    border-radius: 4px 4px 4px 4px;
    background-color: #fffacd;
    padding: 10px;
    max-height: 400px;
    overflow: auto;
  }
  #LoadSampleData h3 {
    padding-left: 0;
  }
</style>
{/literal}

<div class="crm-block">
<div class="crm-form-block">

  <div id="help">
    <p>This tool is used to purge existing contact records from the instance database and load clean sample data. Click continue to proceed with the process. <strong>Note that this process will result in data loss as all existing contact records will be deleted.</strong></p>
  </div>

  <div id="purge-load-action" class="crm-section">
    <div class="label">Purge and Reload?</div>
    <div class="content">
      <a href="#"
         title="{ts}Purge and Load Sample Data AJAX{/ts}"
         id="loadData"
         class="button"><span>{ts}Continue{/ts}</span></a>
    </div>
    <div class="clear"></div>
  </div>

  <div id="output" style="display: none;">
    <h3>Loading data...</h3>
    <div class="content"></div>
  </div>

</div>
</div>  

{literal}
<script type="text/javascript">
  cj(function() {
    cj().crmaccordions();
  });

  var procTime = 0;

  cj('#loadData').click(function(){
    var result = confirm('Are you sure you want to purge existing contacts and load sample data?');
    if ( result != true ) {
      return;
    }

    cj('#output').show();
    cj('#purge-load-action').hide();

    //trigger data load
    var dataUrl = "{/literal}{crmURL p='civicrm/nyss/loaddata' h=0 }{literal}";

    cj.ajax({
      url: dataUrl,
      success: function(data, textStatus, jqXHR){
        procTime = data;
        console.log('processing time: ', procTime);
      },
      error: function( jqXHR, textStatus, errorThrown ) {
        return false;
      }
    });

    //get output and write to screen
    var dataUrl = "{/literal}{crmURL p='civicrm/nyss/getoutput' h=0 }{literal}";
    var element = cj('#output .content');
    var complete = false;
    var h = i = 0;

    while ( !complete ) {
      i++;

      cj.ajax({
        url: dataUrl,
        async: false,
        success: function(data, textStatus, jqXHR){
          //console.log('retrieving file');
          //console.log('data: ', data);

          if ( data.search('SCRIPTCOMPLETE') > -1 ) {
            data = data.replace('SCRIPTCOMPLETE', '');
            complete = true;
          }

          element.html('<p>' + data + '</p>');

          h = element[0].scrollHeight;
          element.scrollTop(h);
        },
        error: function( jqXHR, textStatus, errorThrown ) {
          return false;
        }
      });
      //console.log('complete end: ', complete);
    }
  });
</script>
{/literal}
