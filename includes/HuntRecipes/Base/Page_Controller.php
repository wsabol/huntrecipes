<?php

namespace HuntRecipes\Base;

use HuntRecipes\User\SessionController;

class Page_Controller {

    public function get_page_context(SessionController $sess, string $page_title, array $breadcrumbs, array $additional = []): array {
        $user_name = '';
        $title = '';
        $user_id = 0;

        if ($sess->is_started()) {
            if ($sess->has_user()) {
                $user_name = $sess->user()->name;
                $title = $sess->user()->title;
                $user_id = $sess->user()->id;
            }
        }

        $context = array(
            'current_year' => date("Y"),
            'user_name' => '',
            'user_title' => '',
            'user_image' => '/assets/img/user2-160x160.jpg',
            'page_title' => $page_title,
            'breadcrumbs' => $breadcrumbs,
            'current_user_id' => 0,
            'current_user_is_chef' => false,
            'is_production' => IS_PRODUCTION
        );

        if ($sess->has_user()) {
            $context['user_name'] = $user_name;
            $context['user_title'] = $title;
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
