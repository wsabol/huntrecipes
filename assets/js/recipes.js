
$(function(){
  
  var $mainHeaderLink = $('.main-nav ul li a[href="'+window.location.pathname+'"]');
  if ( $mainHeaderLink.length > 0 ) {
    if ( $mainHeaderLink.parent().parent().hasClass('sub-menu') ) {
      $mainHeaderLink.parent().parent().parent().addClass('current-menu-item');
    } else {
      $mainHeaderLink.parent().addClass('current-menu-item');
    }
  }
  
  $('#lnkUserLogoutAuth').click(function(e){
    e.preventDefault();
    $('#frmUserLogoutAuth').submit();
  });
  
  $('.scroll-to-top').click(function(){
    $(window).scrollTop(0);
  });
  
  $('.column-entries').innerHeight($('.column-entries').innerHeight() + 80);
  
  /* HEAD SEARCH */
  $('#btnHeadSearch').click(function(){
    var search = {
      q: $('#q').val(),
      type_id: ( $('#search_type_id').length === 0 ? 0 : $('#search_type_id').val() ),
      course_id: ( $('#search_course_id').length === 0 ? 0 : $('#search_course_id').val() ),
      cuisine_id: ( $('#search_cuisine_id').length === 0 ? 0 : $('#search_cuisine_id').val() ),
      chef_id: ( $('#search_chef_id').length === 0 ? 0 : $('#search_chef_id').val() ),
      ingrList: ( $('.recipefinder .ingredients .ingr-search-wrapper .ingr-search').length > 0 ? getIngredientSearchList() : '' )
    }
    
    if ( $.trim(search.q).length === 0 ) {
      return;
    }
    
    window.location.href = '/browse.php?'+jQuery.param( search );
  });
  
  $('#q').keyup(function(e){
    if ( e.which == 13 ) {
      $('#btnHeadSearch').click();
    }
  });
  
  /* FAVORITES */
  $('main').on('click', '.entry .divSaveToFavorites', function(e){
    e.preventDefault();
    var $btn = $(this);
    var recipe_id = $btn.parent().data('recipe-id');
    if ( recipe_id == 0 || typeof recipe_id == 'undefined' ) {
      console.log('recipe id undefined');
      return;
    }

    var favorite_flag = 1;
    if ( $btn.hasClass('favorite-recipe') ) {
      // already saved remove from table
      favorite_flag = 0;
    }

    $(this).find('i').addClass('fa-spin fa-fw');
    $.ajax({
      url: '/ajax-json/spFavoriteRecipe.json.php',
      type: 'GET',
      data: {
        recipe_id: recipe_id,
        favorite_flag: favorite_flag
      },
      success: function( response ) {
        //console.log(response);
        var favorite_count = parseInt( $btn.find('.favorite-count').text() );

        if ( response.success == 1 && favorite_flag == 1 ) {
          $btn.addClass('favorite-recipe');
          $btn.find('.favorite-count').text( favorite_count + 1 );
        } else if ( response.success == 1 && favorite_flag == 0 ) {
          $btn.removeClass('favorite-recipe');
          $btn.find('.favorite-count').text( favorite_count - 1 );
        }

        if ( response.success == 1 ) {
          if ( window.location.href.includes('/profile.php') ) {
            
            if ( $btn.closest('#favorites').length > 0 ) {
              LoadDivContent( 'profile/chef_portal', '', 'chef-portal', {} );
              LoadDivContent( 'profile/meal_planning', '', 'meal-planning', {} );

            } else if ( $btn.closest('#chef-portal').length > 0 ) {
              LoadDivContent( 'profile/meal_planning', '', 'meal-planning', {} );

            } else if ( $btn.closest('#meal-planning').length > 0 ) {
              LoadDivContent( 'profile/chef_portal', '', 'chef-portal', {} );
            }
            LoadDivContent( 'profile/my_favorites', '', 'favorites', {} );
          }
        } else {
          console.log(response.query);
          window.location.href = '/login.php';
        }

        $btn.find('i').removeClass('fa-spin fa-fw');
      }
    });

    return false;
  });
  
  /* MEAL PLANNING */
  $('main').on('click', '.btnSaveToMealsMain, .btnSaveToMealsSpecific', function(e){
    var recipe_id = 0;
    var week_of = '';
    //console.log(recipe_id);
    //console.log(week_of);
    
    var $btn = $(this);
    var remove = 0;
    if ( $(this).hasClass('btnSaveToMealsSpecific') ) {
      recipe_id = $(this).parent().parent().parent().parent().parent().data('recipe-id');
      week_of = $(this).data('week-of');
      remove = ( $(this).find('i.fa-check').length > 0 ? 1 : 0 );
      $btn = $(this).parent().parent().parent().find('.btnSaveToMealsMain');
    } else {
      recipe_id = $(this).parent().parent().parent().data('recipe-id');
      remove = ( $(this).hasClass('btn-info') ? 1 : 0 );
    }
    
    $.ajax({
      url: '/ajax-json/meal_planning/spAddRecipeToMealPlan.json.php',
      type: 'GET',
      data: {
        recipe_id: recipe_id,
        week_of: week_of,
        remove: remove
      },
      success: function( response ){
        console.log(response);
        if ( response === null ) {
          console.log('btnSaveToMealsMain null response');
        } else if ( response.success === 0 ) {
          console.log('btnSaveToMealsMain sql error: ' + response.query);
        } else {
          
          // success
          if ( window.location.href.includes('/profile.php') ) {
            if ( response.input_data.this_week_flag == 1 ) {
              if ( $btn.closest('#favorites').length > 0 ) {
                LoadDivContent( 'profile/chef_portal', '', 'chef-portal', {} );

              } else if ( $btn.closest('#chef-portal').length > 0 ) {
                LoadDivContent( 'profile/my_favorites', '', 'favorites', {} );

              } else if ( $btn.closest('#meal-planning').length > 0 ) {
                LoadDivContent( 'profile/chef_portal', '', 'chef-portal', {} );
                LoadDivContent( 'profile/my_favorites', '', 'favorites', {} );
              }
            }
            LoadDivContent( 'profile/meal_planning', '', 'meal-planning', {} );
          }
          if ( response.input_data.this_week_flag == 1 ) {
            $btn.toggleClass('btn-info');
            $btn.toggleClass('btn-theme');
            $btn.find('i').toggleClass('fa-check');
            $btn.find('i').toggleClass('fa-plus');
            $btn.next().toggleClass('btn-info');
            $btn.next().toggleClass('btn-theme');
          }
        }
      }
    });
  });
  
});
/*
if ('serviceWorker' in navigator) {
  navigator.serviceWorker.register('/ServiceWorker.js');
}
*/
