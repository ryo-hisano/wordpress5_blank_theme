<?php get_header(); ?>
<p>トップページ</p>
<?php
$q = new WP_Query(array(
	'posts_per_page' => 5 // 表示件数
));
while ($q->have_posts()): ?>
	<?php $q->the_post(); ?>
	<?php
		// カテゴリ (最初のカテゴリ名のみ。リンク付きですべて表示する場合は the_category(); を使用します)
		$cat = get_the_category();
		$cat_name = isset($cat[0]->cat_name) ? $cat[0]->cat_name : '';
		echo esc_html($cat_name);
	?>
	<?php the_time('Y年m月d日'); /* 投稿の公開日 */ ?>
	<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a><br>
<?php
	wp_reset_postdata();
endwhile ?>
<?php get_footer(); ?>