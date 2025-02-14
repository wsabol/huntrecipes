<?php

namespace HuntRecipes\Base;

use HuntRecipes\User\SessionController;

class Navigation {
    private array $_main_nav;
    private array $_user_nav;
    private array $_footer_nav;

    public function __construct() {
        $sess = new SessionController();

        $this->_main_nav = [
            [
                "title" => "Home",
                "a_href" => "/home/",
                "is_active" => false,
            ],
            [
                "title" => "About",
                "a_href" => "/about/",
                "is_active" => false,
            ],
            [
                "title" => "Recipes",
                "a_href" => "/recipes/",
                "is_active" => false,
            ],
            [
                "title" => "Contact",
                "a_href" => "/contact/",
                "is_active" => false,
            ],
            [
                "title" => "Admin",
                "a_href" => "/admin/",
                "is_active" => false,
                "permission" => function(){
                    if (isset($_SESSION)) {
                        if (isset($_SESSION['User'])) {
                            return $_SESSION['User']->is_developer;
                        }
                    }
                    return false;
                },
                "submenu" => [
                    [
                        "title" => "Chef Approval",
                        "href" => "/admin/chef-approval/"
                    ],
                ]
            ],
        ];

        $this->_footer_nav = [
            [
                "title" => "Home",
                "a_href" => "/home/",
            ],
            [
                "title" => "About",
                "a_href" => "/about/",
            ],
            [
                "title" => "Recipes",
                "a_href" => "/recipes/",
            ],
            [
                "title" => "Contact",
                "a_href" => "/contact/",
            ],
        ];

        if ($sess->has_user()) {
            $this->_user_nav = [
                [
                    "title" => "Favorites",
                    "a_href" => "/account/?goto=favorites",
                    "icon" => "fa fa-heart",
                    "li_class" => "light"
                ],
                [
                    "title" => "My Account",
                    "a_href" => "/account/",
                    "icon" => "fa fa-user",
                    "li_class" => "medium"
                ],
            ];

            if ($sess->user()->is_chef) {
                $this->_user_nav[] = [
                    "title" => "Submit a recipe",
                    "a_href" => "/recipes/recipe/edit/?id=new",
                    "icon" => "icon icon-themeenergy_fork-spoon",
                    "li_class" => "dark"
                ];
            }

            $this->_footer_nav[] = [
                "title" => "My account",
                "a_href" => "/account/",
            ];
        }
        else {
            $this->_user_nav = [
                [
                    "title" => "Sign in",
                    "a_href" => "/account/sign-in/",
                    "icon" => "fa-solid fa-arrow-right-to-bracket",
                    "li_class" => "light"
                ]
            ];

            $this->_footer_nav[] = [
                "title" => "Sign In",
                "a_href" => "/account/sign-in/",
            ];
        }

    }

    public function get_main_nav(): array {
        $main_nav = [];

        $request_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        foreach ($this->_main_nav as $s) {
            $allowed = true;
            if (is_callable(@$s['permission'])) {
                $allowed = $s['permission']();
            }

            if ($allowed) {
                if (@$s['a_href'] == $request_path) {
                    $s['is_active'] = true;
                }

                unset($s['permission']);
                $main_nav[] = $s;
            }


        }

        return $main_nav;
    }

    public function get_user_nav(): array {
        $user_nav = [];

        foreach ($this->_user_nav as $s) {
            $allowed = true;
            if (is_callable(@$s['permission'])) {
                $allowed = $s['permission']();
            }

            if ($allowed) {
                unset($s['permission']);
                $user_nav[] = $s;
            }
        }

        return $user_nav;
    }

    public function get_footer_nav(): array {
        return $this->_footer_nav;
    }
}
