<?php
/**
 * Core functionality for LC HoverPeek.
 *
 * @package lchoverpeek
 */

namespace lchoverpeek;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LCHO_Core {

	private static $instance = null;

	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'wp_enqueue_scripts', [ $this, 'lcho_enqueue_assets' ] );
		add_filter( 'the_content', [ $this, 'lcho_inject_post_id' ] );
		add_action( 'wp_ajax_lcho_preview', [ $this, 'lcho_preview' ] );
		add_action( 'wp_ajax_nopriv_lcho_preview', [ $this, 'lcho_preview' ] );
		add_action( 'wp_ajax_lcho_batch', [ $this, 'lcho_batch' ] );
		add_action( 'wp_ajax_nopriv_lcho_batch', [ $this, 'lcho_batch' ] );
	}

	public function lcho_enqueue_assets() {
		// Performance optimization: only load on singular pages and if the filter is active.
		if ( ! is_singular() || ! has_filter( 'the_content', [ $this, 'lcho_inject_post_id' ] ) ) {
			return;
		}

		$supported_types = get_option( 'lcho_types', [ 'post' ] );
		if ( ! is_array( $supported_types ) ) {
			$supported_types = [];
		}

		if ( ! in_array( get_post_type(), $supported_types, true ) ) {
			return;
		}

		wp_enqueue_style(
			'lcho-style',
			LCHO_PLUGIN_URL . 'assets/hoverpeek.css',
			[],
			LCHO_VERSION
		);

		wp_enqueue_script(
			'lcho-script',
			LCHO_PLUGIN_URL . 'assets/hoverpeek.js',
			[ 'jquery' ],
			LCHO_VERSION,
			true
		);

		wp_localize_script(
			'lcho-script',
			'lcho',
			[
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'lcho_nonce' ),
			]
		);

		/* Dynamic color settings */
		$bg_color      = get_option( 'lcho_bg_color', '#111111' );
		$title_color   = get_option( 'lcho_title_color', '#ffffff' );
		$excerpt_color = get_option( 'lcho_excerpt_color', '#ffffff' );
		$link_color    = get_option( 'lcho_link_color', '#4da3ff' );

		$custom_css = "
			.lcho-popup { background: " . esc_html( $bg_color ) . "; }
			.lcho-popup h4 { color: " . esc_html( $title_color ) . "; }
			.lcho-popup p { color: " . esc_html( $excerpt_color ) . "; }
			.lcho-more { color: " . esc_html( $link_color ) . "; }
		";

		wp_add_inline_style( 'lcho-style', $custom_css );
	}

	public function lcho_inject_post_id( $content ) {
		if ( is_admin() || ! is_main_query() ) {
			return $content;
		}

		$supported_types = get_option( 'lcho_types', [ 'post' ] );
		if ( ! is_array( $supported_types ) ) {
			$supported_types = [];
		}

		if ( ! in_array( get_post_type(), $supported_types, true ) ) {
			return $content;
		}

		if ( false === strpos( $content, '<a' ) ) {
			return $content;
		}

		$site_url = get_site_url();
		$enable_internal = get_option( 'lcho_enable_internal', 1 );
		$enable_external = get_option( 'lcho_enable_external', 0 );

		return preg_replace_callback(
			'/<a\s+([^>]*?)href=["\']([^"\']+)["\']([^>]*)>/i',
			function ( $matches ) use ( $site_url, $enable_internal, $enable_external ) {

				$url     = $matches[2];
				$post_id = url_to_postid( $url );

				if ( $post_id ) {
					if ( ! $enable_internal ) {
						return $matches[0];
					}

					return sprintf(
						'<a data-lcho-post="%1$d"%2$shref="%3$s"%4$s>',
						absint( $post_id ),
						$matches[1],
						esc_url( $url ),
						$matches[3]
					);
				}

				if ( strpos( $url, $site_url ) === false && strpos( $url, 'http' ) === 0 ) {
					if ( ! $enable_external ) {
						return $matches[0];
					}

					return sprintf(
						'<a data-lcho-url="%1$s"%2$shref="%3$s"%4$s>',
						esc_url( $url ),
						$matches[1],
						esc_url( $url ),
						$matches[3]
					);
				}

				return $matches[0];
			},
			$content
		);
	}

	public function lcho_preview() {
		check_ajax_referer( 'lcho_nonce', 'nonce' );

		$post_id = isset( $_POST['post_id'] ) ? absint( wp_unslash( $_POST['post_id'] ) ) : 0;
		$url     = isset( $_POST['url'] ) ? esc_url_raw( wp_unslash( $_POST['url'] ) ) : '';

		if ( $post_id ) {
			$cache_key = 'lcho_int_' . $post_id;
			$cached    = get_transient( $cache_key );

			if ( $cached ) {
				wp_send_json_success( $cached );
			}

			$post = get_post( $post_id );

			if ( ! $post ) {
				wp_send_json_error();
			}

			$data = [
				'post_id' => $post_id,
				'title'   => wp_trim_words( get_the_title( $post ), 10 ),
				'excerpt' => wp_trim_words( get_the_excerpt( $post ), 20 ),
				'link'    => get_permalink( $post ),
				'image'   => get_the_post_thumbnail_url( $post, 'large' ),
			];

			set_transient( $cache_key, $data, 2 * HOUR_IN_SECONDS );
			wp_send_json_success( $data );
		}

		if ( $url ) {
			$data = $this->fetch_external_url( $url );

			if ( 'rate_limit' === $data ) {
				wp_send_json_error( [ 'message' => __( 'Too many requests. Please try again later.', 'lc-hoverpeek' ) ] );
			}

			if ( ! $data ) {
				wp_send_json_error();
			}

			wp_send_json_success( $data );
		}

		wp_send_json_error();
	}

	public function lcho_batch() {
		check_ajax_referer( 'lcho_nonce', 'nonce' );

		$result = [];
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$links  = isset( $_POST['links'] ) ? (array) wp_unslash( $_POST['links'] ) : [];

		foreach ( $links as $item ) {
			$post_id = absint( $item['post_id'] );
			$url     = esc_url_raw( $item['url'] );

			if ( $post_id ) {
				$cache_key = 'lcho_int_' . $post_id;
				$cached    = get_transient( $cache_key );

				if ( $cached ) {
					$result[] = $cached;
					continue;
				}

				$post = get_post( $post_id );
				if ( $post ) {
					$data = [
						'post_id' => $post_id,
						'title'   => wp_trim_words( get_the_title( $post ), 10 ),
						'excerpt' => wp_trim_words( get_the_excerpt( $post ), 20 ),
						'link'    => get_permalink( $post ),
						'image'   => get_the_post_thumbnail_url( $post, 'large' ),
					];

					set_transient( $cache_key, $data, 2 * HOUR_IN_SECONDS );
					$result[] = $data;
				}
			}

			if ( $url ) {
				$data = $this->fetch_external_url( $url );
				if ( $data && 'rate_limit' !== $data ) {
					$result[] = $data;
				}
			}
		}

		wp_send_json_success( $result );
	}

	/**
 * Validate external URL and block SSRF targets.
 *
 * @param string $url URL to validate.
 * @return bool
 */
private function is_safe_external_url( $url ) {

	if ( ! wp_http_validate_url( $url ) ) {
		return false;
	}

	$parts = wp_parse_url( $url );

	if ( empty( $parts['host'] ) ) {
		return false;
	}

	// Only allow HTTP/HTTPS.
	if ( ! in_array( $parts['scheme'], [ 'http', 'https' ], true ) ) {
		return false;
	}

	// Restrict ports.
	if ( ! empty( $parts['port'] ) && ! in_array( (int) $parts['port'], [ 80, 443 ], true ) ) {
		return false;
	}

	$host = strtolower( $parts['host'] );

	// Block localhost.
	if ( in_array( $host, [ 'localhost', 'localhost.localdomain' ], true ) ) {
		return false;
	}

	// Resolve all DNS records.
	$records = dns_get_record( $host, DNS_A | DNS_AAAA );

	if ( empty( $records ) ) {
		return false;
	}

	foreach ( $records as $record ) {

		$ip = '';

		if ( ! empty( $record['ip'] ) ) {
			$ip = $record['ip'];
		}

		if ( ! empty( $record['ipv6'] ) ) {
			$ip = $record['ipv6'];
		}

		if ( empty( $ip ) ) {
			continue;
		}

		if (
			false === filter_var(
				$ip,
				FILTER_VALIDATE_IP,
				FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
			)
		) {
			return false;
		}
	}

	return true;
}

	/**
	 * Securely fetch metadata for an external URL.
	 *
	 * @param string $url The URL to fetch.
	 * @return array|string|false Array of metadata, 'rate_limit' on limit, or false on error.
	 */
	private function fetch_external_url( $url ) {
		$url = esc_url_raw( $url );

		if ( ! wp_http_validate_url( $url ) ) {
			return false;
		}

		$cache_key = 'lcho_ext_' . md5( $url );
		$cached    = get_transient( $cache_key );

		if ( $cached ) {
			return $cached;
		}

		// Rate limiting: check/increment requests bucket (max 20 per minute per IP)
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		if ( $ip ) {
			$key          = 'lcho_limit_' . md5( $ip );
			$limit_data   = get_transient( $key );
			$current_time = time();

			if ( is_array( $limit_data ) && $limit_data['expiry'] > $current_time ) {
				if ( $limit_data['count'] >= 20 ) {
					return 'rate_limit';
				}
				$limit_data['count']++;
				$remaining_time = max( 1, $limit_data['expiry'] - $current_time );
				set_transient( $key, $limit_data, $remaining_time );
			} else {
				$limit_data = [
					'count'  => 1,
					'expiry' => $current_time + 60,
				];
				set_transient( $key, $limit_data, 60 );
			}
		}

		if ( ! $this->is_safe_external_url( $url ) ) {
			return false;
		}
		
		$response = wp_safe_remote_get(
			$url,
			[
				'timeout'             => 5,
				'redirection'         => 3,
				'reject_unsafe_urls'  => true,
				'httpversion'         => '1.1',
				'headers'             => [
					'User-Agent' => 'LC HoverPeek/' . LCHO_VERSION,
				],
			]
		);

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$content_type = wp_remote_retrieve_header( $response, 'content-type' );
		if ( empty( $content_type ) || false === stripos( $content_type, 'text/html' ) ) {
			return false;
		}

		$body = wp_remote_retrieve_body( $response );

		preg_match( '/<title>(.*?)<\/title>/is', $body, $title );
		preg_match( '/<meta name="description" content="(.*?)"/is', $body, $desc );
		preg_match( '/<meta property="og:image" content="(.*?)"/is', $body, $img );

		$data = [
			'post_id' => 0,
			'title'   => isset( $title[1] ) ? wp_strip_all_tags( html_entity_decode( trim( $title[1] ) ) ) : '',
			'excerpt' => isset( $desc[1] ) ? wp_strip_all_tags( html_entity_decode( trim( $desc[1] ) ) ) : '',
			'link'    => $url,
			'image'   => isset( $img[1] ) ? esc_url_raw( trim( $img[1] ) ) : '',
		];

		set_transient( $cache_key, $data, 2 * HOUR_IN_SECONDS );

		return $data;
	}
}
