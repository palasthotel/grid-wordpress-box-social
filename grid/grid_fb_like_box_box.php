<?php
/**
 * @author Palasthotel <rezeption@palasthotel.de>
 * @copyright Copyright (c) 2014, Palasthotel
 * @license http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 * @package Palasthotel\Grid-WordPress-Box-Social
 */

class grid_fb_like_box_box extends grid_static_base_box {

	public function type() {
		return 'fb_like_box';
	}

	public function __construct() {
		parent::__construct();
		$this->content->fb_page     = '';
		$this->content->show_faces  = 'true';
		$this->content->show_header = 'true';
		$this->content->datastream  = 'false';
    	$this->content->language = "de_DE";
		$this->content->colorscheme = 'light';
		$this->content->show_border = 'true';
		$this->content->force_wall  = 'false';
	}

	public function build($editmode) {
		if ( $editmode ) {
			return $this->content;
		} else {
			$fb_page = $this->content->fb_page;
			$show_faces = $this->content->show_faces;
			$show_header = $this->content->show_header;
			$datastream = $this->content->datastream;
			$language = "de_DE";
      		if(isset($this->content->language) && !empty($this->content->language)) { 
      			$language = $this->content->language; 
      		}
			$colorscheme = $this->content->colorscheme;
			$show_border = $this->content->show_border;
			$force_wall = $this->content->force_wall;

			$fb_url = 'http://www.facebook.com/';
			$fbs_url = 'https://www.facebook.com/';
			if ( false === strpos( $fb_page, $fb_url ) && false === strpos( $fb_page, $fbs_url ) ) {
				$fb_page = $fb_url.$fb_page;
			}
			
			ob_start();
			
			grid_social_boxes_init_facebook_js($language);
			
			?>
			<div 
			class="fb-like-box" 
			data-href="<?php echo $fb_page; ?>" 
			data-colorscheme="<?php echo $colorscheme; ?>" 
			data-show-faces="<?php echo $show_faces; ?>" 
			data-header="<?php echo $show_header; ?>" 
			data-stream="<?php echo $datastream; ?>" 
			data-show-border="<?php echo $show_border; ?>"></div>
			<?php
			$output = ob_get_contents();
			ob_end_clean();
			return $output;
		}
	}

	public function contentStructure () {
		$cs = parent::contentStructure();
		return array_merge( $cs, array(
			array(
				'key' => 'fb_page',
				'label' => t( 'Facebook page' ),
				'type' => 'text',
			),
			array(
				'key' => 'width',
				'label' => t( 'Width in pixel (optional, default 300)' ),
				'type' => 'number',
			),
			array(
				'key' => 'height',
				'label' => t( 'Height in pixel(optional, default 556 or 63 without stram and faces)' ),
				'type' => 'number',
			),
			array(
				'key' => 'language',
				'label' => t('Language'),
				'type' => 'select',
				'selections'=>
				array(
					array(
						"key" => "de_DE",
						"text" => t("de_DE"),
					),
					array(
						"key" => "en_US",
						"text" => t("en_US"),
					),
				),
			),
			array(
				'key' => 'colorscheme',
				'label' => t( 'Color scheme' ),
				'type' => 'select',
				'selections' =>
				array(
					array(
						'key' => 'light',
						'text' => t( 'light' ),
					),
					array(
						'key' => 'dark',
						'text' => t( 'dark' ),
					),
				),
			),
			array(
				'key' => 'show_faces',
				'label' => t( 'Faces' ),
				'type' => 'select',
				'selections' =>
				array(
					array(
						'key' => 'true',
						'text' => t( 'show' ),
					),
					array(
						'key' => 'false',
						'text' => t( 'hide' ),
					),
				),
			),
			array(
				'key' => 'show_header',
				'label' => t( 'Box header' ),
				'type' => 'select',
				'selections' =>
				array(
					array(
						'key' => 'true',
						'text' => t( 'show' ),
					),
					array(
						'key' => 'false',
						'text' => t( 'hide' ),
					),
				),
			),
			array(
				'key' => 'datastream',
				'label' => t( 'Stream of latest posts' ),
				'type' => 'select',
				'selections' =>
				array(
					array(
						'key' => 'true',
						'text' => t( 'show' ),
					),
					array(
						'key' => 'false',
						'text' => t( 'hide' ),
					),
				),
			),
			array(
				'key' => 'show_border',
				'label' => t( 'Border of box' ),
				'type' => 'select',
				'selections' =>
				array(
					array(
						'key' => 'true',
						'text' => t( 'show' ),
					),
					array(
						'key' => 'false',
						'text' => t( 'hide' ),
					),
				),
			),
			array(
				'key' => 'force_wall',
				'label' => t( 'Force "place" Pages' ),
				'type' => 'select',
				'selections' =>
				array(
					array(
						'key' => 'true',
						'text' => t( 'On' ),
					),
					array(
						'key' => 'false',
						'text' => t( 'Off' ),
					),
				),
			),
		));
	}
}
