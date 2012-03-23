var json;
var itemselected = false;
var midas = midas || {};

// Prevent error if console.log is called
if (typeof console != "object") {
  var console = {
    'log': function() {}
  };
}

// Main calls
$(function() {

  // Parse json content
  json = jQuery.parseJSON($('div.jsonContent').html());

  // Preload login page
  if(!json.global.logged)
    {
    loadAjaxDynamicBar('login','/user/login');
    }

  // Show log page.
  if(json.global.needToLog)
    {
    showOrHideDynamicBar('login');
    loadAjaxDynamicBar('login','/user/login');
    return;
    }

  // Init Dynamic help ---------------
  InitHelpQtip();
  if(json.global.dynamichelpAnimate)
    {
    TimerQtip();
    }
  else
    {
    StopTimerQtip();
    }
  // Javascript link ---------------------

  // Starting Guide
  $('a#startingGuideLink').click(function()
    {
    showStartingGuide();
    });
  if(json.global.startingGuide)
    {
    showStartingGuide();
    }

  function showStartingGuide()
   {
   $( "#dialogStartingGuide" ).dialog({
      width: 580,
      title: $( "#dialogStartingGuide" ).attr('title'),
      modal: true
      });
   }

   $('#disableStartingGuide').change(function(){
     var value = 1;
     if($(this).is(':checked'))
       {
       value = 0;
       }
     $.post(json.global.webroot+"/user/startingguide", {value: value} );
   });

   $('#blockPersoLink').click(function(){window.location.replace($('.webroot').val()+'/user/userpage/');});
   $('#blockExploreLink').click(function(){window.location.replace($('.webroot').val()+'/browse/');});
   $('#blockCommunityLink').click(function(){window.location.replace($('.webroot').val()+'/community/');});
   $('#blockSettingsLink').click(function(){
      loadAjaxDynamicBar('settings','/user/settings');
      if($("div.TopDynamicBar").is(':hidden'))
        {
          $("div.TopDynamicBar").show('blind', function() {

          });
        }
      $('#dialogStartingGuide').dialog("close");
    });

  // Login
  $("a.loginLink").click(function()
    {
    showOrHideDynamicBar('login');
    loadAjaxDynamicBar('login','/user/login');
    });


  // Account link
  $("li.myAccountLink").click(function()
    {
    if($("div.TopDynamicBar").is(':hidden'))
      {
        $("div.TopDynamicBar").show('blind', function() {

        });
      }
    if($(this).attr('userid')!=undefined)
      {
      loadAjaxDynamicBar('settings'+$(this).attr('userid'),'/user/settings?userId='+$(this).attr('userid'));
      }
    else
      {
      loadAjaxDynamicBar('settings','/user/settings');
      }
    });

  // Setting link
   $("li.settingsLink").click(function()
    {
    if($("div.TopDynamicBar").is(':hidden'))
      {
        $("div.TopDynamicBar").show('blind', function() {
        });
      }
    loadAjaxDynamicBar('settings','/user/settings');
    });

  // Module link
   $("li.modulesLink").click(function()
    {
    if($("div.TopDynamicBar").is(':hidden'))
      {
        $("div.TopDynamicBar").show('blind', function() {
        });
      }
    loadAjaxDynamicBar('settings','/user/settings');
    });


  // Register link
  $("a.registerLink").click(function()
    {
    showOrHideDynamicBar('register');
    loadAjaxDynamicBar('register','/user/register');
    });

  // Search Bar -----------------------
  // Live search
  $.widget( "custom.catcomplete", $.ui.autocomplete, {
    _renderMenu: function( ul, items ) {
      var self = this,
        currentCategory = "";
      $.each( items, function( index, item ) {
        if ( item.category != currentCategory ) {
          ul.append( '<li class="search-category">' + item.category + "</li>" );
          currentCategory = item.category;
        }
        self._renderItem( ul, item );
      });
    }
  });

  var cache = {},
  lastXhr;
  $("#live_search").catcomplete({
  minLength: 2,
  delay: 10,
  source: function( request, response ) {
    var term = request.term;
    if ( term in cache ) {
      response( cache[ term ] );
      return;
    }

    $("#searchloading").show();

    lastXhr = $.getJSON( $('.webroot').val()+"/search/live", request, function( data, status, xhr ) {
      $("#searchloading").hide();
      cache[ term ] = data;
      if ( xhr === lastXhr ) {
        itemselected = false;
        response( data );
      }
      });
   }, // end source
   select: function(event, ui) {
     itemselected = true;
     if(ui.item.itemid) // if we have an item
       {
       window.location.replace($('.webroot').val()+'/item/'+ui.item.itemid);
       }
     else if(ui.item.communityid) // if we have a community
       {
       window.location.replace($('.webroot').val()+'/community/'+ui.item.communityid);
       }
     else if(ui.item.folderid) // if we have a folder
       {
       window.location.replace($('.webroot').val()+'/folder/'+ui.item.folderid);
       }
     else if(ui.item.userid) // if we have a user
       {
       window.location.replace($('.webroot').val()+'/user/'+ui.item.userid);
       }
     else
       {
       window.location.replace($('.webroot').val()+'/search/'+ui.item.value);
       }
     }
   });

  $('#live_search').focus(function() {
    if($('#live_search_value').val() == 'init')
      {
      $('#live_search_value').val($('#live_search').val());
      $('#live_search').val('');
      }
    });

  $('#live_search').focusout(function() {
    if($('#live_search').val() == '')
      {
      $('#live_search').val($('#live_search_value').val());
      $('#live_search_value').val('init');
      }
    });

  $('#live_search').keyup(function(e)
    {
    if(e.keyCode == 13 && !itemselected) // enter key has been pressed
      {
      window.location.replace($('.webroot').val()+'/search/index?q='+encodeURI($('#live_search').val()));
      }
    });



 // Upload -------------------------------------

 // init Upload dialog
  if(json.global.logged)
    {
    $('div.HeaderAction li.uploadFile').qtip(
      {
         content: {
            // Set the text to an image HTML string with the correct src URL to the loading image you want to use
            text: '<img  src="'+json.global.webroot+'/core/public/images/icons/loading.gif" alt="Loading..." />',
            ajax: {
               url: $('div.HeaderAction li.uploadFile').attr('rel') // Use the rel attribute of each element for the url to load
            },
            title: {
               text: 'Upload', // Give the tooltip a title using each elements text
               button: true
            }
         },
         position: {
            at: 'bottom center', // Position the tooltip above the link
            my: 'top right',
            viewport: $(window), // Keep the tooltip on-screen at all times
            effect: true // Disable positioning animation
         },
         show: {
            modal: {
              on: true,
              blur: false
              },
            event: 'click',
            solo: true // Only show one tooltip at a time
         },
         hide: {
          event: false
         },
         style: {
            classes: 'uploadqtip ui-tooltip-light ui-tooltip-shadow ui-tooltip-rounded'
         }
      });
    $('.uploadqtip').css('z-index:500');
    }

  // ask the user to log in if we want to upload a file
  var uploadPageLoaded = false;
  $('div.HeaderAction li.uploadFile').click(function()
  {
    if(json.global.logged)
    {
    if(!uploadPageLoaded)
      {
      $('img#uploadAFile').hide();
      $('img#uploadAFileLoading').show();
      uploadPageLoaded = true;
      }
    }
    else
    {
      createNotive(json.login.contentUploadLogin,4000);
      $("div.TopDynamicBar").show('blind');
      loadAjaxDynamicBar('login','/user/login');
    }
  });

  // Style -------------------------------------

  // hover  link (view Action is the right menu in the file browser)
  $('div.viewAction li a').hover(function(){
    $(this).parents('li').css('background-color','#E5E5E5');
  }, function(){
    $(this).parents('li').css('background-color','white');
  });

  // user menu
    $('#menuUserInfo').click(function(){
      globalAuthAsk(json.global.webroot+'/user/userpage');
    });
  $("div.TopDynamicBar .closeButton").click(function()
  {
    if(!$("div.TopDynamicBar").is(':hidden'))
    {
      $("div.TopDynamicBar").hide('blind');
    }
  });

    $('[qtip]').qtip({
   content: {
      attr: 'qtip'
   }
});

  $('div.TopbarRighta li.first').hover(
      function() {$('ul', this).css('display', 'block');},
      function() {$('ul', this).css('display', 'none');});
});



 // Javascript uilts ----------------------------------

/**
 * Show a jGrowl notice in the top right of the visible screen.
 * @param text The text to display
 * @param delay Time in milliseconds to display the notice
 * @param state (optional) Set to either "error" or "warning" to display special state
 */
function createNotice(text, delay, state) {
    var extraClasses = '';
    if(state == 'error') {
        extraClasses += ' growlError';
    }
    else if(state == 'warning') {
        extraClasses += ' growlWarning';
    }
    else { // state is ok
        extraClasses += ' growlOk';
    }
    createGrowl(false, text, delay, extraClasses);
}

// asks the user to authenticate
function globalAuthAsk(url)
  {
  if(json.global.logged)
    {
    window.location.replace(url);
    }
  else
    {
    createNotive(json.login.titleUploadLogin,4000);
    $("div.TopDynamicBar").show('blind');
    loadAjaxDynamicBar('login','/user/login');
    }
  }

// trim name by the number of character
function sliceFileName(name,nchar)
  {
    if(name.length>nchar)
      {
      toremove=(name.length)-nchar;
      if(toremove<13)
        {
        return name;
        }
      name=name.substring(0,10)+'...'+name.substring(13+toremove);
      return name;
      }
  return name;
  }

// trim name by the number of pixel
 function trimName(name,padding)
  {
    if(name.length*7+padding>350)
      {
      toremove=(name.length*7+padding-350)/8;
      if(toremove<13)
        {
        return 'error';
        }
      name=name.substring(0,10)+'...'+name.substring(name.length+13-toremove);
      return name;
      }
  return name;
  }


 var qtipsHelp = new Array();
 var iQtips = 0;

 function InitHelpQtip()
   {
   if(!json.global.dynamichelp)return ;
   if(json.dynamicHelp == undefined)return;
   $.each(json.dynamicHelp, function(index, value) {
         var text = value.text;
         text = text.replace(/&lt;/g, '<');
         text = text.replace(/&gt;/g, '>');
         var tmp = $(value.selector).qtip({
           content: {
              text: text
           },
           position: {
              my: value.my,  // Position my top left...
              at: value.at // at the bottom right of...
           }
        });
        qtipsHelp.push(tmp);
     });
   }

 // Dynamic help sequence
 function TimerQtip() {
       if(!json.global.dynamichelp)return ;

       $.each(qtipsHelp, function(index, value) {
         value.qtip('hide');
         value.qtip('disable');
        });

        if(json.global.demomode)
          {
          $('.loginLink').qtip('enable');
          }

       if(!$('#dialogStartingGuide').is(':hidden'))
         {
         iQtips = 0;
         setTimeout("TimerQtip()",1000);
         return;
         }

       qtipsHelp[iQtips].qtip('show');
       if(qtipsHelp.length > iQtips+1)
         {
         setTimeout("TimerQtip()",5000);
         }
       else
         {
         setTimeout("StopTimerQtip()",5000);
         }
       iQtips++;
     }

  function StopTimerQtip()
    {
      if(!json.global.dynamichelp)return ;
      $.each(qtipsHelp, function(index, value) {
         value.qtip('hide');
         value.qtip('enable');
        });
    }


// Setup jgrowl --------------------------------------
 window.createGrowl = function(persistent, text, delay, extraClasses) {
    // Use the last visible jGrowl qtip as our positioning target
    var target = $('.qtip.jgrowl:visible:last');

    // Create your jGrowl qTip...
    $(document.body).qtip({
        // Any content config you want here really.... go wild!
        content: {
            text: '<span class="'+extraClasses+'">'+text+'</span>'
        },
        position: {
            my: 'top right', // Not really important...
            at: (target.length ? 'bottom' : 'top') + ' right', // If target is window use 'top right' instead of 'bottom right'
            target: target.length ? target : $(document.body), // Use our target declared above
            adjust: { // show at the top of the visible page, or just below header
                y: Math.max($(window).scrollTop() + 10, $('div.Wrapper').position().top)
            }
        },
        show: {
            event: false, // Don't show it on a regular event
            ready: true, // Show it when ready (rendered)
            effect: function() {
                $(this).stop(0,1).fadeIn(400);
            }, // Matches the hide effect
            delay: 0, // Needed to prevent positioning issues

            // Custom option for use with the .get()/.set() API, awesome!
            persistent: persistent
        },
        hide: {
            event: false, // Don't hide it on a regular event
            effect: function(api) {
                // Do a regular fadeOut, but add some spice!
                $(this).stop(0,1).fadeOut(400).queue(function() {
                    // Destroy this tooltip after fading out
                    api.destroy();

                    // Update positions
                    updateGrowls();
                });
            }
        },
        style: {
            classes: 'jgrowl ui-tooltip-dark ui-tooltip-rounded',
            tip: false // No tips for this one (optional ofcourse)
        },
        events: {
            render: function(event, api) {
                // Trigger the timer (below) on render
                timerGrowl.call(api.elements.tooltip, event, delay);
            }
        }
    })
    .removeData('qtip');
};

   // Make it a window property see we can call it outside via updateGrowls() at any point
   window.updateGrowls = function() {
      // Loop over each jGrowl qTip
      var each = $('.qtip.jgrowl:not(:animated)');
      each.each(function(i) {
         var api = $(this).data('qtip');

         // Set the target option directly to prevent reposition() from being called twice.
         api.options.position.target = !i ? $(document.body) : each.eq(i - 1);
         api.set('position.at', (!i ? 'top' : 'bottom') + ' right');
      });
   };


 function timerGrowl(event, delay)
  {
    var api = $(this).data('qtip'),
       lifespan = delay; // 5 second lifespan

    // If persistent is set to true, don't do anything.
    if(api.get('show.persistent') === true) {return;}

    // Otherwise, start/clear the timer depending on event type
    clearTimeout(api.timer);
    if(event.type !== 'mouseover') {
       api.timerGrowl = setTimeout(api.hide, lifespan);
    }
  }

 $(document).delegate('.qtip.jgrowl', 'mouseover mouseout', timerGrowl);


 // deprecated: use createNotice
function createNotive(text, delay)
{
  createNotice(text,delay, '');
}

