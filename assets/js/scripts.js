(function($){

	"use strict";
	  
	$(document).ready(function () {
		socialchef.init();
		
		socialchef.tree('.sidebar');
		
		/*
		if ('serviceWorker' in navigator) {
			navigator.serviceWorker.register('/ServiceWorker.js');
		}
	*/
	});
	
	var socialchef = {
	
		init: function () {
			//CUSTOM FORM ELEMENTS
			//$('select, input[type=radio],input[type=checkbox],input[type=file]').uniform();
			$('input[type=radio]').iCheck({
				radioClass: 'iradio_flat-red',
        increaseArea: '20%' // optional
			});
			
			//MOBILE MENU
			$('.main-nav').slicknav({
				prependTo:'.head .wrap',
				allowParentLinks : true,
				closeOnClick: true,
				label:''
			});
			
			//SCROLL TO TOP BUTTON
			$('body').on('click', '.scroll-to-top', function (){
				$('body,html').animate({
					scrollTop: 0
				}, 600);
				return false;
			});
			
			//MY PROFILE TABS
			if ( $('.tabs li.active').length === 0 ) {
				$('.tabs li:first').addClass("active");
			}
			if ( $('.tabs li.active').length > 0 ) {
				$('.tab-content').hide();
				$('.tab-content[id='+$('.tabs li.active a').attr('href').replace('#', '')+']').show();
			}
			
			$('.tabs a').on('click', function (e) {
				e.preventDefault();
				$(this).closest('li').addClass("active").siblings().removeClass("active");
				$($(this).attr('href')).show().siblings('.tab-content').hide();
			});

			var hash = $.trim( window.location.hash );
			if (hash) $('.tab-nav a[href$="'+hash+'"]').trigger('click');
			
			//ALERTS
			$('.close').on('click', function (e) {
				e.preventDefault();
				$(this).closest('.alert').hide(400);
			});
			
			//CONTACT FORM 
			$('#contactform').submit(function(){
			
				var action = $(this).attr('action');
				
				if ( $('#message:visible').length == 1 ) {
					$('#message').hide();
				}
				$("#message").show(400);
				$('#submit')
					.after('<img src="assets/images/loading.gif" class="loader" >')
					.attr('disabled','disabled');

				$.post(action, {
					name: $('#name').val(),
					email: $('#email').val(),
					phone: $('#phone').val(),
					comments: $('#comments').val()
				},
				function(data){
					document.getElementById('message').innerHTML = data;
					$('#message').slideDown('slow');
					$('#contactform img.loader').fadeOut('slow', function(){ $(this).remove() });
					$('#submit').removeAttr('disabled');
					//if(data.match('success') != null) $('#contactform').slideUp(3000);
				});
				
				return false; 
			});
			
			//PRELOADER
			$('.preloader').fadeOut();
		},
		pushMenu: {
			activate: function (toggleBtn) {
				//Get the screen sizes
				var screenSizes = {
					xs: 480,
					sm: 768,
					md: 992,
					lg: 1200
				};

				//Enable sidebar toggle
				$(document).on('click', toggleBtn, function (e) {
					e.preventDefault();

					//Enable sidebar push menu
					if ($(window).width() > (screenSizes.sm - 1)) {
						if ($("body").hasClass('sidebar-collapse')) {
							$("body").removeClass('sidebar-collapse').trigger('expanded.pushMenu');
						} else {
							$("body").addClass('sidebar-collapse').trigger('collapsed.pushMenu');
						}
					}
					//Handle sidebar push menu for small screens
					else {
						if ($("body").hasClass('sidebar-open')) {
							$("body").removeClass('sidebar-open').removeClass('sidebar-collapse').trigger('collapsed.pushMenu');
						} else {
							$("body").addClass('sidebar-open').trigger('expanded.pushMenu');
						}
					}
				});

				$(".content-wrapper").click(function () {
					//Enable hide menu when clicking on the content-wrapper on small screens
					if ($(window).width() <= (screenSizes.sm - 1) && $("body").hasClass("sidebar-open")) {
						$("body").removeClass('sidebar-open');
					}
				});

				//Enable expand on hover for sidebar mini
				if ($.socialchef.options.sidebarExpandOnHover
					|| ($('body').hasClass('fixed')
					&& $('body').hasClass('sidebar-mini'))) {
					this.expandOnHover();
				}
			},
			expandOnHover: function () {
				var _this = this;
				var screenWidth = 768 - 1;
				//Expand sidebar on hover
				$('.main-sidebar').hover(function () {
					if ($('body').hasClass('sidebar-mini')
						&& $("body").hasClass('sidebar-collapse')
						&& $(window).width() > screenWidth) {
						_this.expand();
					}
				}, function () {
					if ($('body').hasClass('sidebar-mini')
						&& $('body').hasClass('sidebar-expanded-on-hover')
						&& $(window).width() > screenWidth) {
						_this.collapse();
					}
				});
			},
			expand: function () {
				$("body").removeClass('sidebar-collapse').addClass('sidebar-expanded-on-hover');
			},
			collapse: function () {
				if ($('body').hasClass('sidebar-expanded-on-hover')) {
					$('body').removeClass('sidebar-expanded-on-hover').addClass('sidebar-collapse');
				}
			}
		},
		tree: function (menu) {
			var _this = this;
			var animationSpeed = 250;
			$(document).off('click', menu + ' li a')
				.on('click', menu + ' li a', function (e) {
					//Get the clicked link and the next element
					var $this = $(this);
					var checkElement = $this.next();

					//Check if the next element is a menu and is visible
					if ((checkElement.is('.treeview-menu')) && (checkElement.is(':visible')) && (!$('body').hasClass('sidebar-collapse'))) {
						//Close the menu
						checkElement.slideUp(animationSpeed, function () {
							checkElement.removeClass('menu-open');
							//Fix the layout in case the sidebar stretches over the height of the window
							//_this.layout.fix();
						});
						checkElement.parent("li").removeClass("active");
					}
					//If the menu is not visible
					else if ((checkElement.is('.treeview-menu')) && (!checkElement.is(':visible'))) {
						//Get the parent menu
						var parent = $this.parents('ul').first();
						//Close all open menus within the parent
						var ul = parent.find('ul:visible').slideUp(animationSpeed);
						//Remove the menu-open class from the parent
						ul.removeClass('menu-open');
						//Get the parent li
						var parent_li = $this.parent("li");

						//Open the target menu and add the menu-open class
						checkElement.slideDown(animationSpeed, function () {
							//Add the class active to the parent li
							checkElement.addClass('menu-open');
							parent.find('li.active').removeClass('active');
							parent_li.addClass('active');
							//Fix the layout in case the sidebar stretches over the height of the window
							//_this.layout.fix();
						});
					}
					//if this isn't a link, prevent the page from being redirected
					if (checkElement.is('.treeview-menu')) {
						e.preventDefault();
					}
				});
		}
		
	}
	
})(jQuery);