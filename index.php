<?php get_header(); ?>
<h1>お知らせ 一覧 | お知らせ</h1>
<?php while (have_posts()): ?>
	<?php the_post(); ?>
	<?php
		// カテゴリ (最初のカテゴリ名のみ。リンク付きですべて表示する場合は the_category(); を使用します)
		$cat = get_the_category();
		$cat_name = isset($cat[0]->cat_name) ? $cat[0]->cat_name : '';
		echo esc_html($cat_name);
	?>
	<?php the_time('Y年m月d日'); /* 投稿の公開日 */ ?>
	<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a><br>
<?php endwhile ?>
<?php the_posts_pagination(); /* ページネーション */ ?>
<?php get_footer(); ?>