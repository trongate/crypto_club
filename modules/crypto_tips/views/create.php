<h1><?= $headline ?></h1>
<?= validation_errors() ?>
<div class="card">
    <div class="card-heading">
        Crypto Tip Details
    </div>
    <div class="card-body">
        <?php
        echo form_open($form_location);
        echo form_label('Article Headline');
        echo form_input('article_headline', $article_headline, array("placeholder" => "Enter Article Headline", "autocomplete" => "off"));
        echo form_label('Article Body');
        echo form_textarea('article_body', $article_body, array("placeholder" => "Enter Article Body"));
        echo form_submit('submit', 'Submit');
        echo anchor($cancel_url, 'Cancel', array('class' => 'button alt'));
        echo form_close();
        ?>
    </div>
</div>