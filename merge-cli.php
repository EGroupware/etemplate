#!/usr/bin/php -qC
<?php
/**
 * TranslationTools - CLI to merge translations from all apps and other tree / branches
 *
 * @link http://www.egroupware.org
 * @package admin
 * @author Ralf Becker <RalfBecker-AT-outdoor-training.de>
 * @copyright (c) 2013 by Ralf Becker <RalfBecker-AT-outdoor-training.de>
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 * @version $Id$
 */

if (php_sapi_name() !== 'cli')	// security precaution: forbit calling admin-cli as web-page
{
	die('<h1>merge-cli.php must NOT be called as web-page --> exiting !!!</h1>');
}

chdir($own_tree=dirname(dirname(__FILE__)));	// to enable our relative pathes to work
ini_set('memory_limit', -1);

/**
 * Give usage and exit
 *
 * @Todo: Option to merge in a specific langfile for an app and language outside of the tree(s)
 */
function usage()
{
	echo "\nmerge-cli.php [-h|--help] [-d|--dry-run] [-l|--language lang,...] [-a|--app app,...] [-s|--svn] [<other-tree> ...]\n\n";
	echo "	-d | --dry-run   only report changes without changing anything\n";
	echo "	-l | --language  only process given language(s)\n";
	echo "	-a | --app       only process given app(s)\n";
	echo "	-n | --no-svn    do NOT run a svn update or for new created lang-files svn add\n";
	echo "	--no-svn-up      do NOT run a svn update, but for new created lang-files svn add\n";
	echo "\nmerge-cli resolves missing translations from translations of other apps or optional other trees / branches.\n\n";
	exit;
}

$langs = array();
// read all available languages (other then source "en") from our own tree
foreach(scandir($own_tree.'/phpgwapi/lang') as $lang_file)
{
	if (preg_match('/^egw_([a-z-]+)\.lang$/', $lang_file, $matches) && $matches[1] != 'en') $langs[] = $matches[1];
}

// parse options and optional further trees from command line
$dry_run = $no_svn = $no_svn_up = false;
$trees = array($own_tree);
for($n = 1; $n < $_SERVER['argc']; ++$n)
{
	$val = $_SERVER['argv'][$n];

	if ($val[0] == '-')
	{
		switch($val)
		{
			case '-h': case '--help':
				usage();
				break;

			case '-d': case '--dry-run':
				$dry_run = true;
				break;

			case '-l': case '--language': case '--languages':
				$langs = explode(',', $_SERVER['argv'][++$n]);
				break;

			case '-a': case '--app': case '--apps':
				$only_apps = explode(',', $_SERVER['argv'][++$n]);
				break;

			case '-n': case '--no-svn':
				$no_svn = true;
				break;

			case '--no-svn-up':
				$no_svn_up = true;
				break;

			default:
				echo "\nUnknown option '$val'!\n";
				usage();
				break;
		}
		continue;
	}
	if (!file_exists($val) || !($val = realpath($val)) || !is_dir($val) || !file_exists($val.'/phpgwapi/lang/egw_en.lang'))
	{
		die ("$val is NO EGroupware directory!\n");
	}
	if (!in_array($val, $trees)) $trees[] = $val;
}

// read all langfiles from all trees
$phrases = $apps = array();
foreach($trees as $tree)
{
	if (!$no_svn && !$no_svn_up) system("svn up $tree/*/lang");

	foreach(scandir($tree) as $app)
	{
		if (!is_dir($app) || !file_exists($lang_dir=$tree.'/'.$app.'/lang')) continue;

		foreach(scandir($lang_dir) as $lang_file)
		{
			if (preg_match('/^egw_([a-z-]+)\.lang$/', $lang_file, $matches) &&
				($matches[1] == 'en' || in_array($matches[1], $langs)))
			{
				scan_lang_file($lang_dir.'/'.$lang_file);
			}
		}
		$apps[$tree][] = $app;
	}
}

/**
 * Read all phrases from $path into $langfile for further processing
 *
 * $phrases[$l_id][$l_lang][$tree][$app][$l_app] = $l_translation;
 *
 * @param string $path
 */
function scan_lang_file($path)
{
	global $phrases;

	if (!preg_match('|^(.*)/([^/]+)/lang/egw_([a-z-]+)\.lang$|', $path, $matches))
	{
		die("'$path' is no lang-file path!\n");
	}
	list(,$tree,$app,$lang) = $matches;

	if (!($f = fopen($path, "r")))
	{
		die("Could NOT open $path for reading!\n");
	}
	$line_nr = $read = 0;
	while(($line = fgets($f)))
	{
		$line = explode("\t", trim($line));
		++$line_nr;
		if (count($line) != 4)
		{
			fprintf(STDERR, "WARNING: less then 4 tsv values on line $line_nr of $path!\n");
			continue;
		}
		list($l_id,$l_app,$l_lang,$l_translation) = $line;
		if ($l_lang != $lang)
		{
			fprintf(STDERR, "WARNING: wrong language '$l_lang' on line $line_nr of $path!\n");
			continue;
		}
		@$phrases[strtolower($l_id)][$l_lang][$tree][$app][$l_app] = $l_translation;
		++$read;
	}
	fclose($f);
	echo "$read phrases from $path read\n";
}

// sort all phrases alphabetical, so we dont have to care about that when generating lang-files
ksort($phrases);

//print_r($phrases); exit;

// generate new non-en lang-files from translations of all trees
foreach($langs as $lang)
{
	foreach($trees as $tree)
	{
		foreach($apps[$tree] as $app)
		{
			if (!isset($only_apps) || in_array($app, $only_apps))
			{
				generate_lang_file($tree, $lang, $app, $dry_run);
			}
		}
	}
}

/**
 * Generate new lang-files for a given tree, language and app from translations of all trees
 *
 * @param string $tree
 * @param string $lang
 * @param string $app
 * @param boolena $dry_run=false true: only report changes, but not actually execute them
 */
function generate_lang_file($tree, $lang, $app, $dry_run=false)
{
	global $phrases, $own_tree, $no_svn;

	$lang_file = $tree.'/'.$app.'/lang/egw_'.$lang.'.lang';
	if (($new_file = !file_exists($lang_file)))
	{
		echo "Creating new lang-file $lang_file...";
	}
	else
	{
		echo "Updating lang-file $lang_file...";
	}
	if (!is_writable($lang_file))
	{
		if (is_writable(dirname($lang_file)))
		{
			if (!$dry_run && !$new_file) rename($lang_file, $lang_file.'.old');
		}
		else
		{
			die("\nlang-file $lang_file is NOT writable!\n");
		}
	}
	elseif (!$dry_run && !$new_file && is_writable(dirname($lang_file)))
	{
		@copy($lang_file, $lang_file.'.old');
	}
	if (!$dry_run && !($f = fopen($lang_file, "w")))
	{
		die("\nCould NOT open lang-file $lang_file for writing!\n");
	}
	//$phrases[$l_id][$l_lang][$tree][$app][$l_app] = $l_translation;
	// search translation for all phrases of given tree and lang, based on that trees en translations
	$merged = $existing = $missing = 0;
	foreach($phrases as $phrase => &$langs)
	{
		if (!isset($langs['en'])) continue;	// ignore phrase with no english source (eg. not longer used in sources)

		if (!isset($langs['en'][$tree])) continue;	// phrase not used in requesed tree

		if (!isset($langs['en'][$tree][$app])) continue;	// phrase not used for requested app

		// now we loop alphabetical over all needed phrases
		foreach($langs['en'][$tree][$app] as $l_app => $en_trans)
		{
			if (!isset($langs[$lang]))	// there's no translation for requested lang
			{
				++$missing;
				continue;
			}

			// searching a translation we prefer translations of own tree (should be Trunk or newest)
			// over the requested tree and least any other tree
			foreach(isset($langs[$lang][$own_tree]) ? array($own_tree => $langs[$lang][$own_tree]) :
				(isset($langs[$lang][$tree]) ? array($tree => $langs[$lang][$tree]) : $langs[$lang]) as $p_tree => $apps)
			{
				// we prefer translations for the requested app, over commen ones (no need to store) and over any other apps
				foreach(isset($apps[$app]) ? array($app => $apps[$app]) :
					(isset($apps['phpgwapi']) && isset($apps['phpgwapi']['common']) ?
						array('phpgwapi' => array('common' => $apps['phpgwapi']['common'])) : $apps) as $p_app => $l_apps)
				{
					if ($app != 'phpgwapi' && $p_app == 'phpgwapi')
					{
						break 3;	// no need to store, as we have a common translation (in that lang)
					}

					if (isset($langs[$lang][$tree][$app]))	// existing translation
					{
						++$existing;
					}
					else
					{
						++$merged;
					}

					foreach($l_apps as $translation)
					{
						if (!$dry_run) fwrite($f, "$phrase\t$l_app\t$lang\t$translation\n");
						break;	// one translation is enought
					}
					break;	// one translation is enough
				}
			}
			break;	// one translation is enough
		}
	}
	if (!$dry_run)
	{
		fclose($f);
		if ($new_file)
		{
			if (!$merged)
			{
				unlink($lang_file);	// no need to create empty file
			}
			elseif(!$no_svn)
			{
				system("svn add '$lang_file'");
			}
		}
	}
	echo " --> $merged new phrases merged, $missing still missing, $existing existing phrases\n";
}