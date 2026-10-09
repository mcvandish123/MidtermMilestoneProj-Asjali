<?php
/**
 * Shared create/edit form.
 * Expects: $session, $form (values), $errors, $categoryList, $formAction, $submitLabel.
 */
$ingredientRows = $form['ingredients'] ?: [['quantity' => '', 'name' => '']];
?>
<form class="recipe-form card" method="post" action="<?= e($formAction) ?>" novalidate data-validate>
    <?= csrfField($session) ?>

    <?php if (isset($errors['form'])): ?>
        <div class="form-alert" role="alert"><?= e($errors['form']) ?></div>
    <?php endif; ?>

    <div class="form-grid">
        <div class="field field-wide">
            <label for="title">Recipe title</label>
            <input id="title" name="title" type="text" required
                   minlength="<?= Validator::TITLE_MIN ?>" maxlength="<?= Validator::TITLE_MAX ?>"
                   data-label="Title" value="<?= e($form['title']) ?>"
                   placeholder="e.g. Chicken Tinola ni Lola"
                   aria-describedby="error-title"<?= invalidAttr($errors, 'title') ?>>
            <?= fieldError($errors, 'title') ?>
        </div>

        <div class="field field-wide">
            <label for="description">Short description</label>
            <textarea id="description" name="description" rows="3" required
                      minlength="<?= Validator::DESCRIPTION_MIN ?>" maxlength="<?= Validator::DESCRIPTION_MAX ?>"
                      data-label="Description" data-counter
                      placeholder="What makes this dish special? When do you usually cook it?"
                      aria-describedby="error-description"<?= invalidAttr($errors, 'description') ?>><?= e($form['description']) ?></textarea>
            <?= fieldError($errors, 'description') ?>
        </div>

        <div class="field">
            <label for="category_id">Category</label>
            <select id="category_id" name="category_id" required data-label="Category"
                    aria-describedby="error-category_id"<?= invalidAttr($errors, 'category_id') ?>>
                <option value="">Choose one…</option>
                <?php foreach ($categoryList as $category): ?>
                    <option value="<?= (int) $category['id'] ?>" <?= (string) $category['id'] === (string) $form['category_id'] ? 'selected' : '' ?>>
                        <?= e($category['name']) ?> — <?= e($category['description']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?= fieldError($errors, 'category_id') ?>
        </div>

        <div class="field field-pair">
            <div>
                <label for="servings">Servings <span class="optional">optional</span></label>
                <input id="servings" name="servings" type="number" inputmode="numeric"
                       min="1" max="<?= Validator::SERVINGS_MAX ?>" step="1" data-label="Servings"
                       value="<?= e($form['servings']) ?>" placeholder="4"
                       aria-describedby="error-servings"<?= invalidAttr($errors, 'servings') ?>>
                <?= fieldError($errors, 'servings') ?>
            </div>
            <div>
                <label for="cook_time_minutes">Cooking time <span class="optional">minutes</span></label>
                <input id="cook_time_minutes" name="cook_time_minutes" type="number" inputmode="numeric"
                       min="1" max="<?= Validator::COOK_TIME_MAX ?>" step="1" data-label="Cooking time"
                       value="<?= e($form['cook_time_minutes']) ?>" placeholder="45"
                       aria-describedby="error-cook_time_minutes"<?= invalidAttr($errors, 'cook_time_minutes') ?>>
                <?= fieldError($errors, 'cook_time_minutes') ?>
            </div>
        </div>
    </div>

    <fieldset class="ingredients-fieldset" data-ingredients data-max="<?= Validator::INGREDIENTS_MAX ?>">
        <legend>Ingredients</legend>
        <p class="hint">One ingredient per row. Quantity is optional (e.g. “2 cups”, “1/2 kilo”, “to taste”).</p>

        <ol class="ingredient-list" data-ingredient-list>
            <?php foreach ($ingredientRows as $i => $row): ?>
                <li class="ingredient-row">
                    <span class="ingredient-number" aria-hidden="true"><?= $i + 1 ?></span>
                    <input type="text" name="ingredient_qty[]" class="ingredient-qty"
                           maxlength="<?= Validator::INGREDIENT_QTY_MAX ?>" placeholder="Quantity"
                           aria-label="Quantity for ingredient <?= $i + 1 ?>" value="<?= e($row['quantity']) ?>">
                    <input type="text" name="ingredient_name[]" class="ingredient-name"
                           maxlength="<?= Validator::INGREDIENT_NAME_MAX ?>" placeholder="Ingredient"
                           aria-label="Ingredient <?= $i + 1 ?>" value="<?= e($row['name']) ?>">
                    <button type="button" class="icon-btn" data-remove-ingredient aria-label="Remove ingredient <?= $i + 1 ?>">&times;</button>
                </li>
            <?php endforeach; ?>
        </ol>

        <div class="ingredients-actions">
            <button type="button" class="btn btn-ghost" data-add-ingredient>+ Add ingredient</button>
            <span class="hint" data-ingredient-count></span>
        </div>
        <?= fieldError($errors, 'ingredients') ?>

        <template id="ingredient-row-template">
            <li class="ingredient-row">
                <span class="ingredient-number" aria-hidden="true"></span>
                <input type="text" name="ingredient_qty[]" class="ingredient-qty"
                       maxlength="<?= Validator::INGREDIENT_QTY_MAX ?>" placeholder="Quantity">
                <input type="text" name="ingredient_name[]" class="ingredient-name"
                       maxlength="<?= Validator::INGREDIENT_NAME_MAX ?>" placeholder="Ingredient">
                <button type="button" class="icon-btn" data-remove-ingredient>&times;</button>
            </li>
        </template>
    </fieldset>

    <div class="field">
        <label for="steps">Cooking steps</label>
        <p class="hint" id="steps-hint">Write one step per line. Each line becomes a numbered step.</p>
        <textarea id="steps" name="steps" rows="9" required
                  minlength="<?= Validator::STEPS_MIN ?>" maxlength="<?= Validator::STEPS_MAX ?>"
                  data-label="Cooking steps" data-counter
                  placeholder="Sauté garlic and onion until fragrant.&#10;Add the chicken and cook until lightly browned.&#10;…"
                  aria-describedby="steps-hint error-steps"<?= invalidAttr($errors, 'steps') ?>><?= e($form['steps']) ?></textarea>
        <?= fieldError($errors, 'steps') ?>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn"><?= e($submitLabel) ?></button>
        <a class="btn btn-ghost" href="<?= e($cancelUrl ?? 'recipes.php') ?>">Cancel</a>
    </div>
</form>
