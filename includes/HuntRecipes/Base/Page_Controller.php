<?php

namespace HuntRecipes\Base;

use HuntRecipes\User\SessionController;

class Page_Controller {

    public function get_page_context(SessionController $sess, string $page_title, array $breadcrumbs, array $additional = []): array {
        $user_id = 0;

        if ($sess->is_started()) {
            if ($sess->has_user()) {
                $user_id = $sess->user()->id;
            }
        }

        $nav = new Navigation();

        $context = array(
            'current_year' => date("Y"),
            'main_nav' => $nav->get_main_nav(),
            'user_nav' => $nav->get_user_nav(),
            'footer_nav' => $nav->get_footer_nav(),
            'page_title' => $page_title,
            'breadcrumbs' => $breadcrumbs,
            'current_user_id' => 0,
            'current_user_is_chef' => false,
            'is_production' => IS_PRODUCTION
        );

        if ($sess->has_user()) {
            $context['current_user_id'] = $user_id;
        }

        if (!empty($additional)) {
            foreach ($additional as $key => $value) {
                $context[$key] = $value;
            }
        }

        return $context;
    }
}
