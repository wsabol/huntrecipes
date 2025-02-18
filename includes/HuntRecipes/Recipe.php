<?php

namespace HuntRecipes;

use DateTimeImmutable;
use HuntRecipes\Base\Common_Object;
use HuntRecipes\Database\SqlController;
use HuntRecipes\Exception\HuntRecipesException;
use HuntRecipes\Exception\SqlException;
use HuntRecipes\User\SessionController;

class Recipe extends Common_Object {
    public const IMAGES_DIR = 'assets/images/recipes';

    private SqlController $conn;
    public int $id;
    public int $course_id = 0;
    public int $cuisine_id = 0;
    public int $type_id;
    public int $chef_id = 0;
    public string $title;
    public string $instructions;
    public string $image_filename = 'assets/images/recipes/generic_recipe.jpg';
    public float $serving_count;
    public int $serving_measure_id = 0;
    public int $parent_recipe_id = 0;
    public bool $published_flag = false;

    public function __construct(int $recipe_id, SqlController $conn) {
        $this->id = $recipe_id;
        $this->conn = $conn;

        if ($this->id > 0) {
            $this->update_from_db();
        }
    }

    protected function update_from_db(): void {
        $sel_query = "select * from Recipe where id = {$this->id}";
        $result = $this->conn->query($sel_query);
        if (!!$result) {
            $row = $result->fetch_object();
            $this->course_id = $row->course_id;
            $this->cuisine_id = $row->cuisine_id;
            $this->type_id = $row->type_id;
            $this->chef_id = $row->chef_id;
            $this->title = $row->title;
            $this->instructions = $row->instructions;
            $this->image_filename = $row->image_filename;
            $this->serving_count = $row->serving_count;
            $this->serving_measure_id = $row->serving_measure_id;
            $this->parent_recipe_id = $row->parent_recipe_id;
            $this->published_flag = (bool)$row->published_flag;

            if (!str_starts_with($row->image_filename, "/")) {
                $this->image_filename = "/" . $this->image_filename;
            }
        }
    }

    public static function list(SqlController $conn, array $props): array {
        $props = (object)$props;

        $keyword = $conn->escape_string(@$props->keyword ?? '');
        $recipe_type_id = @$props->recipe_type_id ?? 0;
        $course_id = @$props->course_id ?? 0;
        $cuisine_id = @$props->cuisine_id ?? 0;
        $chef_id = @$props->chef_id ?? 0;
        $ingredients = @$props->ingredients ?? [];

        $current_user_id = 0;
        $sess = new SessionController();
        $sess->start();

        if ($sess->has_user()) {
            $current_user_id = $sess->user()->id;
        }

        $ingredient_sql = "";
        if (!empty($ingredients)) {
            $ingredient_sql = "
            AND r.id IN (
                SELECT ri.recipe_id
                FROM RecipeIngredient ri
                JOIN Ingredient i
                ON ri.ingredient_id = i.id
                WHERE i.name IN(
                    " . implode(',', array_map(fn($v) => "'" . $conn->escape_string($v) . "'", $ingredients)) . "
                )
            )
            ";
        }

        $sel_query = "
        select r.*
            ,co.name as course
            ,cu.name as cuisine
            ,rt.name as type
            ,ch.name as chef
            ,IFNULL((
                SELECT count(1)
                FROM UserRecipeFavorite u
                WHERE u.recipe_id = r.id
            ), 0) as likes_count,
            CASE WHEN urf.id IS NULL THEN 0 ELSE 1 END as is_liked
        from Recipe r
        LEFT JOIN Course co
            ON co.id = r.course_id
        LEFT JOIN Cuisine cu
            ON cu.id = r.cuisine_id
        LEFT JOIN RecipeType rt
            ON rt.id = r.type_id
        LEFT JOIN Chef ch
            ON ch.id = r.chef_id
        LEFT JOIN UserRecipeFavorite urf
            ON urf.recipe_id = r.id
            AND urf.user_id = $current_user_id
        WHERE r.published_flag = 1
          $ingredient_sql
        AND CASE WHEN $recipe_type_id = 0 THEN 1 WHEN $recipe_type_id = r.type_id THEN 1 ELSE 0 END = 1
        AND CASE WHEN $course_id = 0 THEN 1 WHEN $course_id = r.course_id THEN 1 ELSE 0 END = 1
        AND CASE WHEN $cuisine_id = 0 THEN 1 WHEN $cuisine_id = r.cuisine_id THEN 1 ELSE 0 END = 1
        AND CASE WHEN $chef_id = 0 THEN 1 WHEN $chef_id = r.chef_id THEN 1 ELSE 0 END = 1
        AND CASE WHEN '$keyword' = '' THEN 1 WHEN r.title like '%$keyword%' THEN 1 ELSE 0 END = 1
        AND CASE WHEN '$keyword' = '' THEN 1 WHEN r.instructions like '%$keyword%' THEN 1 ELSE 0 END = 1
        order by r.title
        ";
        // echo $sel_query;
        $data = [];

        $result = $conn->query($sel_query);
        if ($result === false) {
            throw new SqlException("Error getting recipes: " . $conn->last_message());
        }

        while ($row = $result->fetch_object()) {
            if (!str_starts_with($row->image_filename, "/")) {
                $row->image_filename = "/" . $row->image_filename;
            }

            $row->link = "/recipes/recipe/?id=" . $row->id;
            $row->is_liked = (bool)$row->is_liked;

            $data[] = $row;
        }

        return $data;
    }

    public static function top_recipes(SqlController $conn): array {
        $top = [];

        $sess = new SessionController();
        $sess->start();

        $sel_query = "
        SELECT
            r.id
        FROM Recipe r
        WHERE r.published_flag = 1
        ORDER BY (
            SELECT count(1)
            FROM UserRecipeFavorite lrf
            WHERE lrf.recipe_id = r.id
        ) DESC,
        RAND()
        LIMIT 6;
        ";
        $result = $conn->query($sel_query);
        if ($result === false) {
            throw new SqlException("Error getting top recipes: " . $conn->last_message());
        }

        while ($row = $result->fetch_object()) {
            $recipe = new Recipe($row->id, $conn);

            $data = $recipe->toObject();
            $data->is_liked = false;
            $data->likes_count = $recipe->get_likes_count();
            $data->link = $recipe->get_link();

            if ($sess->has_user()) {
                $data->is_liked = $recipe->is_liked($sess->user()->id);
            }

            $top[] = $data;
        }

        return $top;
    }

    public static function organize_ingredients_into_columns(array $ingredients, array $child1_ingredients, array $child2_ingredients): array {
        $columns = [[
            'items' => []
        ], [
            'items' => []
        ]];

        if (!empty($child1_ingredients) && !empty($child2_ingredients)) {
            $columns[0]['items'] = $ingredients;
            $columns[1]['items'] = $child1_ingredients;
            $columns[1]['child'] = 1;
            $columns[] = [
                'items' => $child2_ingredients,
                'child' => 2
            ];
            return $columns;
        }

        $main_column_count = 1;

        if (count($ingredients) < 12) {
            $columns[0]['items'] = $ingredients;
        }
        else {
            $main_column_count = 2;
            $breakpoint = ceil(2 * count($ingredients) / 3);
            $columns[0]['items'] = array_slice($ingredients, 0, $breakpoint);
            $columns[1]['items'] = array_slice($ingredients, $breakpoint);
        }

        if (!empty($child1_ingredients))  {

            if ($main_column_count === 1) {
                $columns[1]['items'] = $child1_ingredients;
                $columns[1]['child'] = 1;
            } else {
                $columns[] = [
                    'items' => $child1_ingredients,
                    'child' => 1
                ];
            }
        }

        return $columns;
    }

    public static function set_new_recipe_of_the_day(DateTimeImmutable $date, SqlController $conn) {
        $random_recipe = "
        SELECT id
        FROM Recipe
        WHERE id NOT IN (
            SELECT x.recipe_id
            FROM RecipeOfTheDay x
            WHERE x.day >= DATE_ADD('" . $date->format("Y-m-d") . "', INTERVAL -3 DAY)
        )
        AND published_flag = 1
        ORDER BY RAND()
        LIMIT 1
        ";
        $result = $conn->query($random_recipe);
        if ($result === false) {
            throw new SqlException("Error getting random recipe: " . $conn->last_message());
        }

        $recipe_id = $result->fetch_object()->id;

        $new_query = "
        INSERT INTO RecipeOfTheDay (
                                    day,
                                    recipe_id
        )
        VALUES (
                '" . $date->format("Y-m-d") . "',
                $recipe_id
        );
        ";
        $result = $conn->query($new_query);
        if ($result === false) {
            throw new SqlException("Error setting recipe of the day: " . $conn->last_message());
        }

        $archive_query = "
        DELETE FROM RecipeOfTheDay
        WHERE day < DATE_ADD(CURDATE(), INTERVAL -30 DAY);
        ";
        $result = $conn->query($archive_query);
        if ($result === false) {
            throw new SqlException("Error archiving recipe of the day: " . $conn->last_message());
        }
    }

    protected function exists_in_db(): bool {
        $sel_query = "select * from Recipe where id = {$this->id}";
        $result = $this->conn->query($sel_query);
        if ($result === false) {
            return false;
        }
        return $result->num_rows > 0;
    }

    public function save_to_db(): bool {
        $save_query = "
        INSERT INTO Recipe(
                           course_id,
                           cuisine_id, 
                           type_id,
                           chef_id,
                           title,
                           instructions,
                           image_filename,
                           serving_count,
                           serving_measure_id,
                           parent_recipe_id,
                           published_flag
        ) VALUES (
                  {$this->course_id},
                  {$this->cuisine_id},
                  {$this->type_id},
                  {$this->chef_id},
                  '" . $this->conn->escape_string($this->title) . "',
                  '" . $this->conn->escape_string($this->instructions) . "',
                  '" . $this->conn->escape_string($this->image_filename) . "',
                  {$this->serving_count},
                  {$this->serving_measure_id},
                  {$this->parent_recipe_id},
                  " . ($this->published_flag ? 1 : 0) . "
        );
        
        SELECT LAST_INSERT_ID() as id;
        ";

        if ($this->exists_in_db()) {
            $save_query = "
            UPDATE Recipe
            SET course_id = $this->course_id,
                cuisine_id = $this->cuisine_id,
                type_id = $this->type_id,
                chef_id = $this->chef_id,
                title = '" . $this->conn->escape_string($this->title) . "',
                instructions = '" . $this->conn->escape_string($this->instructions) . "',
                image_filename = '" . $this->conn->escape_string($this->image_filename) . "',
                serving_count = $this->serving_count,
                serving_measure_id = $this->serving_measure_id,
                parent_recipe_id = $this->parent_recipe_id,
                published_flag = " . ($this->published_flag ? 1 : 0) . "
            WHERE id = {$this->id};
            
            SELECT {$this->id} as id;
            ";
        }

        $updResult = $this->conn->query($save_query);
        if ($updResult === false) {
            throw new SqlException('Error saving Recipe: ' . $this->conn->last_message());
        }

        $row = $updResult->fetch_object();
        $this->id = $row->id;

        return true;
    }

    public function delete_from_db(): bool {
        $delete_query = "
        DELETE FROM RecipeIngredient
        WHERE recipe_id = {$this->id};
        ";
        $result = $this->conn->query($delete_query);
        if ($result === false) {
            throw new SqlException('Error deleting Recipe: ' . $this->conn->last_message());
        }

        $delete_query = "
        DELETE FROM Recipe
        WHERE id = {$this->id};
        ";
        $result = $this->conn->query($delete_query);
        if ($result === false) {
            throw new SqlException('Error deleting Recipe: ' . $this->conn->last_message());
        }
        return true;
    }

    public static function recipe_of_the_day(SqlController $conn): self {
        $result = $conn->query("SELECT recipe_id FROM RecipeOfTheDay ORDER BY day DESC LIMIT 1");
        if ($result === false) {
            throw new SqlException("Error getting recipe of the day: " . $conn->last_message());
        }
        if ($result->num_rows === 0) {
            throw new SqlException("recipe of the day does not exist");
        }

        $row = $result->fetch_object();
        return new self($row->recipe_id, $conn);
    }

    public static function top_recipe_categories(SqlController $conn): array {
        $categories = [];

        $sel_query = "
        SELECT *
        FROM (
            select
                rt.id AS entity_id,
                'recipe_type' AS ctype,
                rt.name,
                rt.icon,
                (
                    SELECT count(1) FROM Recipe r
                    WHERE r.type_id = rt.id
                    AND published_flag = 1
                ) recipe_count
            FROM RecipeType rt
            UNION 
            select
                rc.id AS entity_id,
                'cuisine' AS ctype,
                rc.name,
                rc.icon,
                (
                    SELECT count(1) FROM Recipe r
                    WHERE r.cuisine_id = rc.id
                    AND published_flag = 1
                ) recipe_count
            FROM Cuisine rc
            UNION
            select
                c.id AS entity_id,
                'course' AS ctype,
                c.name,
                c.icon,
                (
                    SELECT count(1) FROM Recipe r
                    WHERE r.course_id = c.id
                    AND published_flag = 1
                ) recipe_count
            FROM Course c
        ) rcat
        WHERE icon != ''
        ORDER BY rcat.recipe_count DESC
        LIMIT 24;
        ";

        $result = $conn->query($sel_query);
        if ($result === false) {
            throw new SqlException("Error getting top categories: " . $conn->last_message());
        }
        if ($result->num_rows === 0) {
            throw new SqlException("Did not find any categories");
        }

        $counter = 0;
        while ($row = $result->fetch_object()) {

            if ( $counter % 4 == 0 ) {
                $row->class = "light";
            } elseif ( $counter % 4 == 2 ) {
                $row->class = "dark";
            } else {
                $row->class = "medium";
            }

            $categories[] = $row;
            $counter++;
        }

        return $categories;
    }

    public function get_report_type(): string {
        if ($this->type_id === 0) {
            return "";
        }
        return (new RecipeType($this->type_id, $this->conn))->name;
    }

    public function get_course(): string {
        if ($this->course_id === 0) {
            return "";
        }
        return (new Course($this->course_id, $this->conn))->name;
    }

    public function get_cuisine(): string {
        if ($this->cuisine_id === 0) {
            return "";
        }
        return (new Cuisine($this->cuisine_id, $this->conn))->name;
    }

    public function get_chef(): string {
        if ($this->chef_id === 0) {
            return "";
        }
        return (new Chef($this->chef_id, $this->conn))->name;
    }

    public function is_liked(int $user_id): bool {
        $sel_query = "
        SELECT *
        FROM UserRecipeFavorite
        WHERE recipe_id = $this->id
        AND user_id = $user_id
        ";
        $result = $this->conn->query($sel_query);
        if ($result === false) {
            throw new SqlException("Error getting liked status: " . $this->conn->last_message());
        }
        return $result->num_rows > 0;
    }

    public function get_likes_count(): int {
        $sel_query = "
        SELECT *
        FROM UserRecipeFavorite
        WHERE recipe_id = $this->id
        ";
        $result = $this->conn->query($sel_query);
        if ($result === false) {
            throw new SqlException("Error getting like count: " . $this->conn->last_message());
        }
        return $result->num_rows;
    }

    public function get_link(): string {
        return "/recipes/recipe/?id=$this->id";
    }

    public function get_ingredients(): array {
        $ingredients = [];

        $sel_query = "
        SELECT
            ri.id recipe_id,
            ri.ingredient_id,
            i.name raw_ingredient_name,
            i.name_plural raw_ingredient_name_plural,
            ri.ingredient_prep,
            ri.amount,
            ri.measure_id,
            CASE WHEN ri.amount > 1
                THEN m.name_plural
                ELSE m.name
                END measure,
            m.measure_type_id,
            CASE WHEN m.abbr = ''
                THEN (CASE WHEN ri.amount > 1
                THEN m.name_plural
                ELSE m.name END)
                ELSE m.abbr
                END measure_abbr,
            ri.amount * m.general_unit_conversion as general_measure_amount,
            mt.general_measure_id,
            ri.optional_flag
        FROM RecipeIngredient ri
        JOIN Ingredient i
        ON i.id = ri.ingredient_id
        JOIN Measure m
        ON m.id = ri.measure_id
        JOIN MeasureType mt
        ON mt.id = m.measure_type_id
        WHERE ri.recipe_id = $this->id
        ORDER BY m.measure_type_id DESC,
            ri.amount * m.general_unit_conversion DESC,
            i.name ASC;
        ";

        $result = $this->conn->query($sel_query);
        if ($result === false) {
            throw new SqlException("Error getting recipe ingredients: " . $this->conn->last_message());
        }

        while ($row = $result->fetch_object()) {
            $row->value_formatted = (new RecipeAmount($row->amount, $row->measure_id))->amount_formatted();
            $row->name_formatted = $row->raw_ingredient_name;

            $ingredients[] = $row;
        }

        return $ingredients;
    }

    public function get_instructions(): array {
        return array_values(array_filter(explode("\n", $this->instructions), "strlen"));
    }

    public function get_users_who_liked_this(): array {
        $users = [];

        $sel_query = "
        SELECT
            urf.user_id,
            u.name,
            u.profile_picture
        FROM UserRecipeFavorite urf
        JOIN User u
        ON u.id = urf.user_id
        WHERE urf.recipe_id = $this->id
        ORDER BY RAND()
        LIMIT 9;
        ";

        $result = $this->conn->query($sel_query);
        if ($result === false) {
            throw new SqlException("Error getting users who liked this recipe: " . $this->conn->last_message());
        }

        while ($row = $result->fetch_object()) {
            if (!str_starts_with($row->profile_picture, "/")) {
                $row->profile_picture = "/" . $row->profile_picture;
            }
            $users[] = $row;
        }

        return $users;
    }

    public function set_user_favorite(int $user_id, bool $toggle_status = true): void {
        if (!$toggle_status) {
            $del_query = "
            DELETE FROM UserRecipeFavorite
            WHERE recipe_id = {$this->id}
            AND user_id = {$user_id}
            ";
            $result = $this->conn->query($del_query);
            if ($result === false) {
                throw new SqlException("Error deleting favorite: " . $this->conn->last_message());
            }
            return;
        }

        $already_liked = $this->is_liked_by_user($user_id);

        if ($already_liked) {
            return;
        }

        $ins_query = "
        INSERT INTO UserRecipeFavorite(
                                       recipe_id,
                                       user_id
        )
        VALUES(
               $this->id,
               $user_id
        )
        ";

        $result = $this->conn->query($ins_query);
        if ($result === false) {
            throw new SqlException("Error adding recipe favorite: " . $this->conn->last_message());
        }
    }

    /**
     * @return Recipe[]
     * @throws SqlException
     */
    public function get_child_recipes(): array {
        $children = [];

        $sel_query = "
        select r.id
        from Recipe r
        WHERE r.parent_recipe_id = $this->id
        ";

        $result = $this->conn->query($sel_query);
        if ($result === false) {
            throw new SqlException("Error getting recipes: " . $this->conn->last_message());
        }

        while ($row = $result->fetch_object()) {
            $children[] = new Recipe($row->id, $this->conn);
        }

        return $children;
/*
        $RecipeChildren = array();
        if ( $Recipe['child_count'] > 0 ) {
            $par_query = "
		SELECT id FROM Recipe WHERE parent_recipe_id = ".$App->R['recipe_id'].";
	";
            $pResult = $App->oDBMY->query($par_query);
            while ( $pRow = $pResult->fetch_assoc() ) {
                $chl_query = "
			Call spSelectRecipe(".$pRow['id'].", ".(@$_SESSION['Login']['id']*1).");
		";
                $cResult = $App->oDBMY->query($chl_query);
                if ( !!$cResult ) {
                    $cRow = $cResult->fetch_assoc();
                    $cResult->free();

                    $cRow['ingredients'] = array();
                    $ching_query = "
				Call spSelectRecipeIngredients(".$pRow['id'].");
			";
                    $ciResult = $App->oDBMY->query($ching_query);
                    if ( !!$ciResult ) {
                        while ( $ciRow = $ciResult->fetch_array() ) {
                            array_push($cRow['ingredients'], $ciRow);
                        }
                        array_push($RecipeChildren, $cRow);
                        $Recipe['ingredient_count'] += $cRow['ingredient_count'];
                    }
                }
            }
            $pResult->free();
            $Recipe['child_count'] = count($RecipeChildren);
        }*/

    }

    public function is_liked_by_user(int $user_id): bool {
        $del_query = "
        SELECT *
        FROM UserRecipeFavorite
        WHERE recipe_id = {$this->id}
        AND user_id = {$user_id}
        ";
        $result = $this->conn->query($del_query);
        return $result->num_rows > 0;
    }

    /**
     * @param RecipeIngredient[] $recipe_ingredients
     * @return bool
     * @throws HuntRecipesException|SqlException
     */
    public function set_recipe_ingredients(array $recipe_ingredients): bool {

        // recipe check
        foreach ($recipe_ingredients as $recipe_ingredient) {
            if ($recipe_ingredient->recipe_id != $this->id) {
                throw new HuntRecipesException("This RecipeIngredient does not belong to this recipe");
            }
        }

        // save
        foreach ($recipe_ingredients as $recipe_ingredient) {
            $recipe_ingredient->save_to_db();
        }

        // remove non matches
        $existing_ids = [];
        foreach ($recipe_ingredients as $recipe_ingredient) {
            $existing_ids[] = $recipe_ingredient->id;
        }

        $del_query = "
        DELETE FROM RecipeIngredient
        WHERE recipe_id = {$this->id}
        AND id not in(" . implode(',', $existing_ids) . ")
        ";
        $this->conn->query($del_query);

        return true;
    }
}
