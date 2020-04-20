<?php
// WordPressデフォルトの「投稿」URLを/info/で表示されるように
function post_has_archive($args, $post_type) {
	if ('post' == $post_type) {
		$args['rewrite'] = true;
		$args['has_archive'] = 'info';
	}
	return $args;
}

add_filter('register_post_type_args', 'post_has_archive', 10, 2);

// WordPressデフォルトの「投稿」リンクを/info/XXXに置換
function add_article_post_permalink($permalink) {
	$permalink = '/info'.$permalink;
	return $permalink;
}

add_filter('pre_post_link', 'add_article_post_permalink');

// WordPressデフォルトの「投稿」詳細URLを/info/XXXに置換
function add_article_post_rewrite_rules($post_rewrite) {
	$return_rule = array();
	foreach ($post_rewrite as $regex => $rewrite) {
		$return_rule['info/'.$regex] = $rewrite;
	}
	return $return_rule;
}

add_filter('post_rewrite_rules', 'add_article_post_rewrite_rules');

// カスタム投稿タイプの作成
function create_post_type() {
	register_post_type('column', [ // 投稿タイプ名の定義
		'labels' => [
			'name' => 'コラム', // 管理画面上で表示する投稿タイプ名
			'singular_name' => 'column', // カスタム投稿の識別名
		],
		'public' => true, // 投稿タイプをpublicにするか
		'has_archive' => true, // アーカイブ機能ON/OFF
		'menu_position' => 4, // 管理画面上での配置場所
		'show_in_rest' => true // 5系から出てきた新エディタ「Gutenberg」を有効にする
	]);
}

add_action('init', 'create_post_type');

// 管理画面ナビ名変更（「投稿」を「お知らせ」に）
function change_post_menu_label() {
	global $menu;
	global $submenu;
	$menu[5][0] = 'お知らせ';
	$submenu['edit.php'][5][0] = 'お知らせ一覧';
	$submenu['edit.php'][10][0] = '新しいお知らせ';
	$submenu['edit.php'][16][0] = 'タグ';
}

add_action('init', 'change_post_object_label');

function change_post_object_label() {
	global $wp_post_types;
	$labels = &$wp_post_types['post']->labels;
	$labels->name = 'お知らせ';
	$labels->singular_name = 'お知らせ';
	$labels->add_new = _x('追加', 'お知らせ');
	$labels->add_new_item = 'お知らせの新規追加';
	$labels->edit_item = 'お知らせの編集';
	$labels->new_item = '新規お知らせ';
	$labels->view_item = 'お知らせを表示';
	$labels->search_items = 'お知らせを検索';
	$labels->not_found = '記事が見つかりませんでした';
	$labels->not_found_in_trash = 'ゴミ箱に記事は見つかりませんでした';
}

add_action('admin_menu', 'change_post_menu_label');

// 管理画面からナビを非表示化
function remove_menus() {
	remove_menu_page('edit-comments.php'); // コメント
	remove_submenu_page('edit.php', 'edit-tags.php?taxonomy=category'); // 投稿 / カテゴリー
	remove_submenu_page('edit.php', 'edit-tags.php?taxonomy=post_tag'); // 投稿 / タグ
}

add_action('admin_menu', 'remove_menus', 999);

// Gutenberg自動付与のCSSを削除
function dequeue_plugins_style() {
	wp_dequeue_style('wp-block-library');
}

add_action('wp_enqueue_scripts', 'dequeue_plugins_style', 9999);

// headタグ内のjQueryを読み込ませない（自前でjQueryは用意）
function my_delete_local_jquery() {
	wp_deregister_script('jquery');
}

add_action('wp_enqueue_scripts', 'my_delete_local_jquery');

// 自動で読み込まれる絵文字対応のJavaScriptとCSSを無効化
remove_action('wp_head', 'print_emoji_detection_script', 7);
remove_action('wp_print_styles', 'print_emoji_styles', 10);

// コメントのフィードや特定のカテゴリのフィードのリンクを無効化
remove_action('wp_head', 'feed_links_extra', 3);

// ブログ投稿ツールのためのタグを無効化
remove_action('wp_head', 'rsd_link');
remove_action('wp_head', 'wlwmanifest_link');

// WordPressバージョン表示の無効化
remove_action('wp_head', 'wp_generator');

// rel="next"、rel="prev"を削除
remove_action('wp_head', 'adjacent_posts_rel_link_wp_head', 10);

// DNS Prefetchingタグ削除
remove_action('wp_head','wp_resource_hints', 2);

// wp-jsonを削除
remove_action('wp_head','rest_output_link_wp_head');

// oEmbed埋め込みカードタグを削除
remove_action('wp_head','wp_oembed_add_discovery_links');
remove_action('wp_head','wp_oembed_add_host_js');

// 短縮URLを削除
remove_action('wp_head', 'wp_shortlink_wp_head');

// All in One SEO Packが出力するprev/nextリンクを削除
add_filter('aioseop_prev_link', '__return_empty_string');
add_filter('aioseop_next_link', '__return_empty_string');

// titleタグ付与
add_theme_support('title-tag');

// CSS読み込み
function add_files() {
	if(is_front_page()) {
		// トップページ
		$slug = 'top';
		$ver = '200420';
	} else if(is_post_type_archive('post') || get_post_type() === 'post') {
		// お知らせ（/info/、/info/XXX）時
		// デフォルト「投稿」のスラッグ名は「post」
		$slug = 'info';
		$ver = '200420';
	} else if(is_post_type_archive('column') || get_post_type() === 'column') {
		// コラム（/column/、/column/XXX）時
		$slug = 'column';
		$ver = '200420';
	} else {
		// 固定ページ時
		if($current_id === '') $current_id === $post->ID;
		$par_id = get_post($current_id)->post_parent;
		$most_par_id = $current_id;
		while($par_id != 0):
			$par_post = get_post($par_id);
			$most_par_id = $par_post->ID;
			$par_id = $par_post->post_parent;
		endwhile;
		$slug = get_post($most_par_id)->post_name;

		// キャッシュ対策
		$ver = '200420';
		if($slug === 'information') {
			$ver = '200420';
		}
	}

	// サイト共通のCSS読み込み（末尾がリビジョン番号）
	wp_enqueue_style('style', get_template_directory_uri().'/css/style.css', '', '200420');

	// ページ独自のCSS読み込み
	wp_enqueue_style($slug, get_template_directory_uri().'/css/'.$slug.'.css', '', $ver);
}

add_action('wp_enqueue_scripts', 'add_files');

function replace_link_stylesheet_tag($tag) {
	return preg_replace(array("/'/", '/(id|type|media)=".+?" */', '/ \/>/' ), array('"', '', '>'), $tag);
}

add_filter('style_loader_tag', 'replace_link_stylesheet_tag');