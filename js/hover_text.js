jQuery(document).ready( function ($) {
var local_text ="";
var local_text_trimed="";
var english_org_text;
$('label,h1,h2,h3,span,a,input,option').each(function(){
  
   var $this = $(this);
   $this_global = $(this);

  // do the hover job hover(inFunction,outFunction)
  $this.hover(function(){
    
    //jQuery('<div class="tooltip_rdhil">Wait for translate...</div>')
    //.appendTo('body');
    //changeTooltipPosition(event)   
     
    local_text = $this.text();
    local_text_trimed = local_text.trim();
    var site_url = objectFromPhp.websiteUrl;
    var post_id = "id_of_post";
    if ( local_text != "")
    {    
          jQuery('<div class="tooltip_rdhil">Wait for translate...</div>')
          .appendTo('body');
          changeTooltipPosition(event)
		  
           if (local_text_trimed != '')
           {
              jQuery.ajax({
       
                  type: "POST",
                  url: site_url,
                  data : {
                      action : 'rdhil_get_translation_origin',
                      website_text : local_text_trimed
                  },
                 success: function(data, status) {
                 english_org_text = data; 
                 if (( english_org_text != "") && (local_text_trimed == $this.text().trim()))                  
                     jQuery('.tooltip_rdhil').text(english_org_text);
                 },
                 error: function(xhr, desc, err) {
                 console.log(xhr);
                 console.log("Details: " + desc + "\nError:" + err);
                 }
               }); // Ajax Call 
  
               if (english_org_text == "") {
                   english_org_text = 'Wait for translate...';
               }
           }//if (local_text_trimed != '')
           else
               english_org_text = 'Wait for translate...';
    }// if ( local_text != "")  
      
    }//, function(){
    ,function () {
          // do nothing
  });// $this.hover(function(){

    var changeTooltipPosition = function(event) {
        width = ($(window).width() / event.clientX );
        if ($(window).width() / event.clientX > 2)
          var tooltipX = event.pageX + 8;  
        else 
          var tooltipX = event.pageX - 308;  
        var tooltipHeight = jQuery('div.tooltip_rdhil').height();
        var tooltipY = event.pageY - tooltipHeight - 40;
        if ( local_text != "")
          jQuery('div.tooltip_rdhil').css({top: tooltipY, left: tooltipX,'z-index': '120000','text-align':'left'});
     }; //  var changeTooltipPosition = function(event) {
  
     var hideTooltip = function() {
    	jQuery('div.tooltip_rdhil').remove();
     };
  
      $this.bind({
    	mousemove : changeTooltipPosition,
    	mouseenter : showTooltip,
    	mouseleave: hideTooltip
     });    
 
     var showTooltip = function(event) {
     jQuery('div.tooltip_rdhil').remove();
     }     
  
 }); // });//$('a,div,article,aside,footer,header,main,nav,section,summery,ul,li,h1,h2,h3').each(function(){
  
}); // jQuery(document).ready( function ($) {  