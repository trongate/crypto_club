<h1>Today's Crypto Tips</h1>
<p>Lorem ipsum dolor, sit amet consectetur adipisicing elit. Nobis molestiae pariatur quibusdam debitis ea harum delectus autem, itaque ullam. Commodi nisi minima totam error ipsam vitae at ut rerum reiciendis!</p>
<hr>

<ul>
	<?php
	foreach($tips as $tip) {
		echo '<li>';
        echo anchor('crypto_tips/display/'.$tip->id, $tip->article_headline);
		echo '</li>';
	}
	?>
</ul>