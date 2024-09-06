<?php

namespace HuntRecipes\Base;

use HuntRecipes\User\SessionController;

class Navigation {
    private array $_main_nav;
    private array $_user_nav;

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
                "title" => "Featured",
                "a_href" => "/featured/",
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
                "title" => "Developer",
                "a_href" => "/developer/",
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
                        "title" => "Recipe Process",
                        "href" => "/API/v0/recipe_process/recipe_process.php"
                    ],
                    [
                        "title" => "Servings Redux",
                        "href" => "/API/v0/recipe_process/servings_redux.php"
                    ],
                    [
                        "title" => "Ingredients Redux",
                        "href" => "/API/v0/recipe_process/ingredients_redux.php"
                    ],
                    [
                        "title" => "Recipe Type Redux",
                        "href" => "/API/v0/recipe_process/recipe_type_redux.php"
                    ],
                    [
                        "title" => "SocialChef Demo",
                        "href" => "/API/v0/SocialChefDemo/HTML/",
                        "target" => "_blank"
                    ],
                ]
            ],


        ];

        if ($sess->has_user()) {
            $this->_user_nav = [
                [
                    "title" => "My account",
                    "a_href" => "/profile/",
                    "icon" => "fa fa-user",
                    "li_class" => "light"
                ],
                [
                    "title" => "Favorites",
                    "a_href" => "/profile/favorites",
                    "icon" => "fa fa-heart",
                    "li_class" => "light"
                ]
            ];

            if ($_SESSION['User']->is_chef) {
                $this->_user_nav[] = [
                    [
                        "title" => "Submit a recipe",
                        "a_href" => "/recipes/submit/",
                        "icon" => "icon icon-themeenergy_fork-spoon",
                        "li_class" => "medium"
                    ]
                ];
            }
        }
        else {
            $this->_user_nav = [
                [
                    "title" => "Login",
                    "a_href" => "/login/",
                    "icon" => "fa-solid fa-arrow-right-to-bracket",
                    "li_class" => "light"
                ]
            ];
        }
    }

    public function get_main_nav(): array {
        $main_nav = [];

        foreach ($this->_main_nav as $s) {
            $allowed = true;
            if (is_callable(@$s['permission'])) {
                $allowed = $s['permission']();
            }

            if ($allowed) {
                if (@$s['a_href'] == $_SERVER['REQUEST_URI']) {
                    $s['is_active'] = true;
                }
            }

            $main_nav[] = $s;
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
                if (@$s['a_href'] == $_SERVER['REQUEST_URI']) {
                    $s['li_class'] .= " current-menu-item";
                }
            }

            $user_nav[] = $s;
        }

        return $user_nav;
    }
}
