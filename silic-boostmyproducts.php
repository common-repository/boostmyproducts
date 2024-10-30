<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
/**
 * Plugin Name: Boostmyproducts
 * Plugin URI: http://www.boostmyproducts.com/plugin-pour-wordpress/
 * Description: Intégrez et gérez facilement votre liste de produits ou services sur votre site à partir d'une feuille de calcul type Excel
 * Version: 1.0.9
 * Author: Solution Internet
 * Author URI:  http://www.solution-internet.com/
 * License: GPL2 license
 * Text Domain: boostmyproducts
 */
 
 /**
  * Ce module permet d'appeler un webservice qui fournit une liste de produits à intégrer
  * C'est un fichier csv sous la forme titre;contenu,url;contenu_index
  * Ce modules contient une page de configuration pour laquelle il faut renseigner: url du webservice, identfifnat client, clé d'identification
  * Pour mettre à jour les pages il faut appeler l'url http://mon-site.com/?silic-plugin=boostmyproducts-execute
  * 
  */
 


define('SILIC_BMPRODUCTS_VERSION', '1.0.9' );
define('SILIC_BMPRODUCTS_MAIL_ERREUR', 'admin@solution-internet.com');

function silic_boostmyproducts_query_vars($vars) {
    $vars[] = 'silic-plugin';
    return $vars;
}
add_filter('query_vars', 'silic_boostmyproducts_query_vars');


add_action('parse_request', 'silic_boostmyproducts_doit');
add_action('plugins_loaded', 'silic_boostmyproducts_load_textdomain');

function silic_boostmyproducts_load_textdomain() {
	load_plugin_textdomain( 'boostmyproducts', false, dirname( plugin_basename(__FILE__) ) . '/languages/' );
}

// hook pour page d'administration
function silic_boostmyproducts_admin() {
    include('silic-boostmyproducts-admin.php');
}

// hook pour creation menu "boostmyproducts"
function silic_boostmyproducts_admin_actions() {
    add_options_page("Boostmyproducts", "Boostmyproducts", 1, "silic-boostmyproducts", "silic_boostmyproducts_admin");
}
 
add_action('admin_menu', 'silic_boostmyproducts_admin_actions');

function silic_boostmyproducts_doit($wp) {
	if(preg_match("/\/silic_boostmyproducts_execute.*/",$_SERVER["REQUEST_URI"]) || (array_key_exists('silic-plugin', $wp->query_vars) 
            && $wp->query_vars['silic-plugin'] == 'boostmyproducts-execute'))
   	{

      	echo "<h2>R&eacute;cup&eacute;ration des produits</h2>";
      	
      	global $user_ID;
      	
      	$error = false;
      	
      	$silicawt_webservice_url = get_option('silicawt_webservice_url');
        $silicawt_client_code = get_option('silicawt_client_code');
        $silicawt_client_pwd = get_option('silicawt_client_pwd');
      
 		$comptes = explode(";",$silicawt_client_code);

		foreach($comptes as $compte)
		{
			if(isset($_GET['cli']) && $_GET['cli'] == $compte || !isset($_GET['cli']))
			{
				if(!isset($_GET['cli']))
					echo silic_boostmyproducts_msg("\n\n######## Traitement compte $compte #############\n\n");
				
				$silicawt_client_code = $compte;
				
				try{
					$contents = json_decode(file_get_contents($silicawt_webservice_url.'?client='.$silicawt_client_code.'&pass='.$silicawt_client_pwd),true);
				}
				catch (Exception $e) {
					$error = true;
					mail(SILIC_BMPRODUCTS_MAIL_ERREUR,'[BOOSTMYPRODUCTS][WORDPRESS]['.$compte.'] - Erreur recuperation infos compte', $e->getMEssage());
				}
				
	
				if(isset($contents['last_version']) && $contents['last_version'] != SILIC_BMPRODUCTS_VERSION)
				{
					mail(SILIC_BMPRODUCTS_MAIL_ERREUR,'[BOOSTMYPRODUCTS][WORDPRESS]['.$compte.'] - Mise a jour module wordpress boostmyproducts', 'Client '.$_SERVER['HTTP_HOST']);
				}
				
				if(isset($contents['contenu_index']))
				{
					$contenu_index = $contents['contenu_index'];
				}
				else
				{
					$contenu_index = "";
				}
				
				/*$contents['url'] = "http://cardan.boostmyproducts.com//csv/produits.csv";
				$contents['titre_index'] = "Boostmyproducts - Liste des cardans";
				$contents['contenu_index'] = "";*/
				
				
				$index = "";
				$i = 0;
				if(isset($contents['url']) && !$error)
				{
					$nb_traites = 0;
					$nb_ajout = 0;
					$nb_a_jour = 0;
					$nb_maj = 0;
					$nb_suppr = 0;
					$compteur_pageid = 0;
					$nb_diff = 0;
					if(isset($contents['titre_index']))
					{
						$titre_index = $contents['titre_index'];
					}
					else
					{
						$titre_index = "Boostmyproducts - Liste des produits $compte";	
					}
	
					$url_index_parent = "boostmyproducts-$compte";	
					$url_index_google = "boostmyproducts-$compte-index";
					
					// récupération page index par url
					$page_google = get_page_by_path($url_index_google, OBJECT, 'page');
					if($page_google->ID>0)
					{
						$page_index_google['ID'] = $page_google->ID;
					}
					$page_index_google['post_type']    = 'page';
					$page_index_google['post_parent']  = 0;
					$page_index_google['post_status']  = 'publish';
					$page_index_google['post_title']   = $titre_index." - index";
					$page_index_google['post_name'] = $url_index_google;
					$page_index_google = apply_filters('boostmyproducts_add_new_page', $page_index_google, 'boostmyproducts');
					$page_google_id = wp_insert_post ($page_index_google);
					
					$id_parent = 0;
					// récupération page index par url
					$page_parent = get_page_by_path($url_index_parent, OBJECT, 'page');
					if($page_parent->ID>0)
					{
						$id_parent = $page_parent->ID;
						$page_index_parent['ID'] = $page_parent->ID;
					}
					$page_index_parent['post_type']    = 'page';
					$page_index_parent['post_parent']  = 0;
					$page_index_parent['post_content']  = $contenu_index;
					$page_index_parent['post_status']  = 'publish';
					$page_index_parent['post_title']   = $titre_index;
					$page_index_parent['post_name'] = $url_index_parent;
					$page_index_parent = apply_filters('boostmyproducts_add_new_page', $page_index_parent, 'boostmyproducts');
					kses_remove_filters();
					$page_parent_id = wp_insert_post ($page_index_parent);
					kses_init_filters();
					
					if($id_parent == 0)
					{
						$id_parent = $page_parent_id;
					}
	
					try{
						$file = fopen($contents['url'],"r");
					}
					catch (Exception $e) {
						$error = true;
						mail(SILIC_BMPRODUCTS_MAIL_ERREUR,'[BOOSTMYPRODUCTS][WORDPRESS]['.$compte.'] - Erreur recuperation csv', $e->getMEssage());
					}
					
					if(!$error)
					{
						$traites = array();
						while (($ligne = fgetcsv($file, 5000, ";")) !== FALSE) {
							$num_ligne = $i+1;
							if(isset($ligne[2]))
							{
								$maj = true;
								$pagew = get_page_by_path($url_index_parent."/".$ligne[2], OBJECT, 'page');
								if($pagew->ID>0)
								{
									if($pagew->post_content == $ligne[1] && $pagew->post_title == $ligne[0])
									{
										$nb_a_jour++;
										$maj = false;
									}
								}	
							}
							
							
							if(!isset($ligne[2]))
							{
								echo silic_boostmyproducts_msg("\nPas d'url pour le produit ".$ligne[0]);
							}
							if($i>0 && $maj && isset($ligne[2]))
							{
								if($pagew->ID>0)
								{
									$page['ID'] = $pagew->ID;
									$nb_maj++;
								}
								else
								{
									$nb_ajout++;
								}
								$page['post_type']    = 'page';
								$page['post_content'] = (string)$ligne[1];
								$page['post_parent']  = $id_parent;
								$page['post_status']  = 'publish';
								$page['post_title']   = $ligne[0];
								$page['post_name'] = $ligne[2];
								$page = apply_filters('boostmyproducts_add_new_page', $page, 'boostmyproducts');
								
	/*							if($i<20)
								{*/
									kses_remove_filters();
									$pageid = wp_insert_post ($page);
									kses_init_filters();
									//wp_set_post_terms( $pageid, array('boostmyproducts'));
	//							}
								$compteur_pageid++;
	
								if ($pageid == 0)
								{
									$error = true;
									echo silic_boostmyproducts_msg("\nErreur à l'insertion de la page ".$page['post_title']);
									mail(SILIC_BMPRODUCTS_MAIL_ERREUR,'[BOOSTMYPRODUCTS][WORDPRESS]['.$compte.'] - Erreur insertion page',print_r($page,true));
								}
							}
							if($i > 0)
							{
								if(!in_array(strtolower($ligne[2]),$traites))
								{
									$traites[] = strtolower($ligne[2]);
									$nb_diff++;
								}
								$index .= "<br><a href=\"".$ligne[2]."\">".$ligne[0]."<a>";
								$nb_traites++;
							}
							$page = NULL;
							$i++;
						}
		
						
						$my_wp_query = new WP_Query();
						$all_wp_pages = $my_wp_query->query(array('post_type' => 'page', 'posts_per_page' => -1));
						
						$client_children = get_page_children( $id_parent, $all_wp_pages );
	
						foreach($client_children as $child)
						{
							if(!in_array(strtolower($child->post_name), $traites))
							{
								if($child->ID>0)
								{
									wp_delete_post($child->ID);
									$nb_suppr++;
								}
							}
						}
						
						
						$contenu_index = "<center><a href=\"".$url_index_parent."\">Pour faciliter la recherche rendez-vous sur cette page</a></center><br/><br/>".$index;
						
						// récupération page index par url
						$page_google_fin = get_page_by_path($url_index_google, OBJECT, 'page');
						if($page_google_fin->ID>0)
						{
							$page_index_google_fin['ID'] = $page_google_fin->ID;
						}
						$page_index_google_fin['post_type']  = 'page';
						$page_index_google_fin['post_content']  = $contenu_index;
						$page_index_google_fin['post_parent']  = 0;
						$page_index_google_fin['post_status']  = 'publish';
						$page_index_google_fin['post_title']   = $titre_index." - index";
						$page_index_google_fin['post_name'] = $url_index_google;
						$page_index_google_fin = apply_filters('boostmyproducts_add_new_page', $page_index_google_fin, 'boostmyproducts');
						$page_google_fin_id = wp_insert_post ($page_index_google_fin);
					}
					
					fclose($file);
					
					echo silic_boostmyproducts_msg("\nNombre de lignes trait&eacute;es : ".$nb_traites);
					echo silic_boostmyproducts_msg("\nNombre de produits diff&eacute;rents trait&eacute;s (URL) : ".$nb_diff);
					echo silic_boostmyproducts_msg("\nNombre de produits ajout&eacute;s : ".$nb_ajout);
					echo silic_boostmyproducts_msg("\nNombre de produits mis &agrave; jour : ".$nb_maj);
					echo silic_boostmyproducts_msg("\nNombre de produits &agrave; jour : ".$nb_a_jour);
					echo silic_boostmyproducts_msg("\nNombre de produits supprim&eacute;s : ".$nb_suppr);
				}
			}
		}
      exit();
   }
}

function silic_boostmyproducts_msg($message)
{
	if (php_sapi_name() != 'cli') {
		$msg = nl2br($message);
	}
	else
		$msg = $message;
	return $msg;
}



 ?>