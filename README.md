# WordPress 5.4 最小限テーマ

カテゴリもタグ機能も使わず、クライアントが書き分けしやすいようにカスタム投稿タイプのみ使うケース。

## 公開時想定

| ページ名         | URL               | ファイル           |
| ---------------- | ----------------- | ------------------ |
| トップページ     | /                 | front-page.php     |
| 投稿一覧         | /info/            | index.php          |
| 投稿詳細         | /info/info-1/     | single.php         |
| カスタム投稿一覧 | /column/          | archive-column.php |
| カスタム投稿詳細 | /column/column-1/ | single-column.php  |
| 固定ページ       | /test/            | test-page.php      |
| 404              | /error            | 404.php            |

## 「投稿」URL を/info/で表示されるように変更

通常、投稿一覧は`/`、投稿詳細は`/post-1/`という URL だが、これを投稿一覧は`/info/`、投稿詳細は`/info/post-1/`としたい場合を想定。  
※ functions.php をアップロードしたあと、WordPress 管理画面のパーマリンク設定で「変更を保存」を行わないと反映されないので注意（項目の変更は不要）。

```php
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
```

## カスタム投稿タイプの作成

下記では「コラム」という名称のカスタム投稿タイプを増やしている（register_post_type は複数設定可能）。  
管理画面のナビに「コラム」という項目が増え、通常の投稿のように作成可能。

カスタム投稿一覧には /column/ でアクセスでき、archive-column.php を作成するとコラム一覧のみの表示カスタマイズが可能。  
カスタム投稿詳細には /column/column-1/ でアクセスでき、single-column.php を作成するとコラム詳細のみの表示カスタマイズが可能。

```php
// カスタム投稿タイプの作成
function create_post_type() {
	register_post_type('column', \[ // 投稿タイプ名の定義
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
```

## デフォルトで「投稿」と表示される箇所の変更

管理画面での「投稿」という表示名を変更したい場合。

```php
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
```

## 管理画面からナビを非表示化

下記では「コメント」「投稿 / カテゴリー」「投稿 / タグ」のメニューを消している。

```php
// 管理画面からナビを非表示化
function remove_menus() {
	remove_menu_page('edit-comments.php'); // コメント
	remove_submenu_page('edit.php', 'edit-tags.php?taxonomy=category'); // 投稿 / カテゴリー
	remove_submenu_page('edit.php', 'edit-tags.php?taxonomy=post_tag'); // 投稿 / タグ
}

add_action('admin_menu', 'remove_menus', 999);
```

## ページ毎の CSS 読み込みに対応（TOP 以外は固定ページのスラッグ名.css を使う）

あまりに細かく CSS ファイルを分けないよう、一番親のスラッグ名.css を読み込む。  
例）/test1/page/、/test1/test2/page/ という詳細ページの場合、両方とも test1.css を読み込む。

並びに\$ver 変数でリビジョン番号の手動更新によるキャッシュ対策も行う。

```php
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
```

## 不要タグの削除

```php
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
```

## シングルクォートの箇所があるのを修正

デフォルトではシングルクォートが用いられ、不要な id や type、media 属性が付与されてしまう。空要素の閉じタグも付いてしまう。

```php
<link rel='stylesheet' id='theme-css' href='http://example.com/wp/wp-content/themes/theme-name/style.css' type='text/css' media='all' />
```

こちらも functions.php 内に記述。

```php
function replace_link_stylesheet_tag($tag) {
	return preg_replace(array("/'/", '/(id|type|media)=".+?" \*/', '/ \\/>/' ), array('"', '', '>'), $tag);
}

add_filter('style_loader_tag', 'replace_link_stylesheet_tag');
```

下記のようにきれいになる。

```php
<link rel="stylesheet" href="http://example.com/wp/wp-content/themes/theme-name/style.css">
```

また、WordPress を非公開設定していると付いている、`<meta name='robots' content='noindex,nofollow' />` については本番時無くなるので OK。

[（参考）WordPress：wp_enqueue_style()で出力された link タグを自分好みに変更する方法 | NxWorld](https://www.nxworld.net/wordpress/wp-change-link-tag-output-by-wp-enqueue-style.html)

## ログイン時に表示される管理者バーを消す

ログイン時にサイトの表画面を見るとヘッダ上に表示されるツールバーが邪魔な場合。
WordPress 管理画面のユーザー編集から、「ツールバー」項目のチェック「サイトを見るときにツールバーを表示する」を OFF に。

## 最新のリセット CSS を採用

[（参考）古い CSS リセットからはもう卒業！モダンブラウザに適した新しい CSS リセット -A Modern CSS Reset | コリス](https://coliss.com/articles/build-websites/operation/css/a-modern-css-reset.html)

## 最新の jQuery を採用

```php
<script src="<?php echo get_theme_file_uri(); ?>/js/jquery-3.5.0.min.js"></script>
```
