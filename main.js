"use strict";function createCookie(t,e,n){var o=new Date;o.setTime(o.getTime()+24*n*60*60*1e3),document.cookie=encodeURI(t)+"="+encodeURI(e)+";domain=."+document.domain+";path=/;; expires="+o.toGMTString()}function readCookie(t){for(var e,n=t+"=",o=document.cookie.split(";"),a=0;a<o.length;a++){for(e=o[a];" "===e.charAt(0);)e=e.substring(1,e.length);if(0===e.indexOf(n))return e.substring(n.length,e.length)}return null}$=$||jQuery,jQuery(function(t){t(".search-result-item-image").hover(function(){t(".search-result-item-headline",t(this).parent()).addClass("search-hover")},function(){t(".search-result-item-headline",t(this).parent()).removeClass("search-hover")})}),jQuery(function(t){t("#comments-link").click(function(){t("html, body").animate({scrollTop:t("#comments").offset().top-100},2e3)})}),jQuery(function(t){var e=readCookie("greenpeace"),n=t("body").data("nro");if(null==e){t(".cookie-notice").show();var o=t(".cookie-notice").height();t("footer").css("margin-bottom",o+"px")}else createCookie("gp_nro",n,365);t("#hidecookie").click(function(){t(".cookie-notice").slideUp("slow"),t("footer").css("margin-bottom","0"),createCookie("greenpeace","2",365),createCookie("no_track","0",-1),createCookie("gp_nro",n,365),window.dataLayer=window.dataLayer||[],dataLayer.push({event:"cookiesConsent"})})}),jQuery(function(t){t(".country-select-dropdown").click(function(){t(this).parent().toggleClass("active-li"),t(".country-select-box").toggle()}),t(".country-select-box .country-list li").click(function(){t(this).parents(".country-select-box").find("li").removeClass("active"),t(this).addClass("active")}),t(".country-selectbox").click(function(){t(this).toggleClass("active"),t(this).parent().find(".option-contry").toggleClass("active")});var e=t("#countries_script");if(e.length>0){var n=JSON.parse(e.text()),o=t('<div id="country-list" class="country-list"><a class="international" href=""></a><ul class="countries_list"></ul></div>');t.each(n,function(e,n){if("0"===e)t(".international",o).attr("href",n[0].url).text(n[0].name);else{var a=t('<li><h3 class="country-group-letter">'+e+'</h3><ul class="countries_sublist"></ul></li>');t(".countries_list",o).append(a),t.each(n,function(e,n){t.each(n.lang,function(e,o){t(".countries_sublist",a).append('<li><a href="'+o.url+'">'+n.name+" | "+o.name+"</a></li>")})})}}),t("#navbar-dropdown #country-select").append(o)}}),jQuery(function(t){var e=window.location.host;t(".page-template a, article a").each(function(){var n=void 0===t(this).attr("href")?"":t(this).attr("href");if(""!=n&&n.indexOf(e)<=-1&&n.length>0){if(0==t(this).text().trim().length)return;"/"!=n.charAt(0)&&"#"!=n.charAt(0)&&(t(this).append('<svg class="external-icon" width="284px" height="284px" viewBox="0 0 284 284" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">\n      <g id="Page-1" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">\n          <g fill="currentColor" fill-rule="nonzero">\n              <path d="M14.3,283.7 C14.1,283 13.4,283.1 13,282.9 C4.8,279.9 0.5,273.8 0.5,265 C0.5,190.1 0.5,115.2 0.5,40.3 C0.5,33 0.5,25.7 0.5,18.4 C0.5,7.9 8.2,0.2 18.6,0.2 C56.5,0.2 94.4,0.2 132.4,0.2 C138.7,0.2 142.2,3.7 142.2,9.9 C142.2,15.2 142.2,20.4 142.2,25.7 C142.2,32.1 138.7,35.6 132.3,35.6 C101.2,35.6 70.1,35.6 39,35.6 C36.6,35.6 35.8,36 35.8,38.6 C35.9,107.3 35.9,176 35.8,244.8 C35.8,247.8 36.6,248.3 39.4,248.3 C108,248.2 176.5,248.2 245.1,248.3 C248.1,248.3 248.6,247.4 248.6,244.7 C248.5,213.9 248.5,183.1 248.5,152.3 C248.5,145.3 251.9,142 258.8,142 C263.3,142 267.9,142.2 272.4,141.9 C277.9,141.5 281.8,143.5 283.9,148.6 C283.9,189 283.9,229.4 283.9,269.8 C282.4,272.4 281.5,275.4 279.4,277.8 C276.5,280.9 272.9,282.5 269,283.7 C184.1,283.7 99.2,283.7 14.3,283.7 Z" id="Shape"></path>\n              <path d="M284,100.5 C283.3,100.6 283.3,101.3 283,101.7 C279.7,107.4 273.2,108.3 268.6,103.7 C258.7,93.9 248.9,84.1 239.1,74.1 C237.5,72.4 236.7,72.6 235.1,74.2 C206.7,102.7 178.3,131.1 149.8,159.5 C144.5,164.8 139.9,164.8 134.6,159.5 C131.1,156 127.7,152.6 124.2,149.1 C119.6,144.4 119.6,139.5 124.2,134.9 C152.7,106.4 181.2,77.9 209.7,49.5 C211.6,47.6 211.6,46.8 209.7,45 C200,35.5 190.5,25.9 180.9,16.3 C177.8,13.3 176.5,9.9 178.3,5.8 C180,1.8 183.4,0.3 187.7,0.4 C216,0.5 244.3,0.5 272.6,0.4 C278,0.4 281.7,2.1 283.9,7.1 C284,38.1 284,69.3 284,100.5 Z" id="Shape"></path>\n          </g>\n      </g>\n  </svg>'),t(this).attr("target","_blank"),t(this).addClass("external-link"))}})}),jQuery(function(t){t(".page-template img, .post-content img").each(function(){t(this).attr("title",t(this).attr("alt"))})}),jQuery(function(t){if(t(document).on("click",[".navbar-dropdown-toggle",".country-dropdown-toggle",".navbar-search-toggle"].join(),function(e){e.preventDefault(),e.stopPropagation();var n=t(this),o=n.data("target");if(!o)throw new Error("Missing `data-target` attribute: specify the container to be toggled");var a=n.data("toggle");if(!a)throw new Error("Missing `data-toggle` attribute: specify the class to toggle");t(o).toggleClass(a),t(this).toggleClass(a),n.attr("aria-expanded",function(t,e){return"false"===e?"true":"false"})}),t(document).on("click",function(e){var n=e.target;t('button[aria-expanded="true"]').each(function(e,o){var a=t(o),i=t(a.data("target")).get(0);i&&!t.contains(i,n)&&a.trigger("click")})}),t(document).bind("keyup",function(e){27===e.which&&t(document).trigger("click")}),t(document).on("click",".close-navbar-dropdown",function(e){e.preventDefault(),t(".navbar-dropdown-toggle").trigger("click")}),t(window).width()<=768){var e,n=t(".top-navigation").outerHeight();t(window).scroll(function(){e=!0}),setInterval(function(){e&&(!function(e,n,o){var a=t(this).scrollTop();Math.abs(e-a)<=n||(a>e&&a>o?t(".top-navigation").removeClass("nav-down").addClass("nav-up"):a+t(window).height()<t(document).height()&&t(".top-navigation").removeClass("nav-up").addClass("nav-down"),e=a)}(0,5,n),e=!1)},250);var o=t(".mobile-menus");t(document).click(function(){t(".menu").hasClass("active")&&(o.animate({left:0==parseInt(o.css("left"),10)?-320:0}),t(".menu").removeClass("active")),t(".search-box").hasClass("active")&&a.slideToggle().toggleClass("active")}),t(".menu").click(function(){event.stopPropagation(),t(this).toggleClass("active"),o.animate({left:-320==parseInt(o.css("left"),10)?0:-320})});var a=t("#search .search-box");t("#search-trigger").on("click",function(t){t.stopPropagation(),a.slideToggle().toggleClass("active")})}}),jQuery(function(t){t('div.wp-caption[class*="align"]').each(function(){var e=t(this).find("img").attr("width");t(this).css("cssText","width: "+e+"px !important;")})});var load_more=$("button.load-more-mt");load_more.off("mousedown touchstart").on("mousedown touchstart",function(t){t.preventDefault();var e=$(this.dataset.content),n=parseInt(this.dataset.page)+1,o=parseInt(this.dataset.total),a=this.dataset.url+"?page=".concat(n);this.dataset.page=n,$.ajax({url:a,type:"GET",dataType:"html"}).done(function(t){e.append(t),n===o&&load_more.fadeOut()}).fail(function(t,e,n){console.log(n)})}),jQuery(function(t){t('a[href$=".pdf"]').each(function(){var e=t(this);e.parent("h1, h2, h3, h4, h5, h6").length||e.has("img").length||e.addClass("icon-link pdf-link")})}),jQuery(function(t){function e(){t(".image-zoomer").fadeOut(500,function(){t(".image-zoomer-content").html("")})}t(".post-content img").each(function(){t(this).parents(".block").length||t(this).off("click").on("click",function(){var n;n=t(this).clone(),t(".image-zoomer-content").html(""),t(n).appendTo(".image-zoomer-content"),t(".image-zoomer").fadeIn(),t(".image-zoomer").off("click").on("click",e)})})});
//# sourceMappingURL=main.js.map
