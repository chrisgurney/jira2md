<?
include '_jiraenv.php';

// ----------------------------------------------------------------
// check for tool prerequisites
// ----------------------------------------------------------------

// check if pandoc is installed; error message otherwise
if (!command_exists('pandoc')) {
	echo 'This tool requires pandoc. Download from http://pandoc.org/'."\n\n";
	exit(1);
}

// ----------------------------------------------------------------
// get environment and command-line vars
// ----------------------------------------------------------------

# TODO? second mode via parameter that allows for sprint plan output vs issue output
#   some test output included issues that were in earlier sprints but not completed

$usage = 
	'JIRA to Word Doc, Markdown, Text converter.'."\n"
	.'Usage: php '.$argv[0].' -p JIRA_PROJECT_KEY [-j "JQL"]'."\n\n"
	.'  JIRA Credentials:'."\n"
	.'  Store your JIRA credentials in the JUSER and JPW'."\n"
	.'  environment variables.'."\n\n"	
  .'  JQL:'."\n"
  .'  Double-quotes in JQL must be escaped, or you may'."\n"
  .'  use single quotes.'."\n"  
  .'  Default: "Project = JIRA_PROJECT_KEY AND Type in (Story, Task)'."\n" 
  .'  AND Status NOT IN (Done) ORDER BY Sprint, Rank"'."\n\n";

$args = getopt("j:p:");
$args_jql = $args['j'];
$args_project = $args['p'];

// check if the JIRA environment variables are set
if ($_ENV['JUSER'] == '' || $_ENV['JPW'] == '') {
  echo $argv[0].': Set JUSER and JPW environment variables.'."\n\n";
  exit(1);
}

// check if required parameters were provided
if ($args_project == '') {
  echo $usage;
  exit(1);
}

// if JQL wasn't provided, use the default JQL query with the project key
if ($args_jql == '') {
	$args_jql = str_replace('[PROJECT_KEY]', $args_project, JIRA_DEFAULT_JQL);
	echo 'JQL not provided. Using: '."\n";
	echo '"'.$args_jql.'"'."\n";
}

// ----------------------------------------------------------------
// remove old output files
// ----------------------------------------------------------------

echo 'Removing old output files...';
exec('rm out_*', $exec_return);
echo ' Done!'."\n";

// ----------------------------------------------------------------
// get all issues
// ----------------------------------------------------------------

echo 'Downloading Issues JSON...';

$issues_json_string = curl_get_json(JIRA_BASE_JQL.'&jql='.urlencode($args_jql), $_ENV['JUSER'], $_ENV['JPW']);
$issues_json = json_decode($issues_json_string, TRUE);

file_put_contents('out_jira.json', json_encode($issues_json, JSON_PRETTY_PRINT)); // optional

// if JSON includes "errorMessages", output them and exit
if ($issues_json['errorMessages'] != '') {
	$errorMessages = $issues_json['errorMessages'];
	foreach ($errorMessages as $errorMessage) {
		echo "\n".'Error: '.$errorMessage."\n\n";
		exit(1);
	}
}

$issues = $issues_json['issues'];

echo ' Done!'."\n";

// ----------------------------------------------------------------
// get all epics, and create a lookup table with epic keys -> names
// (epic names are not included in story results)
// ----------------------------------------------------------------

echo 'Downloading Epics JSON...';

$epic_jql = 'project%20%3D%20'.$args_project.'%20AND%20type%20%3D%20Epic';

$epics_json_string = curl_get_json(JIRA_EPIC_URL.'&jql='.$epic_jql, $_ENV['JUSER'], $_ENV['JPW']);
$epics_json = json_decode($epics_json_string, TRUE);

file_put_contents('out_jira_epics.json', json_encode($epics_json, JSON_PRETTY_PRINT)); // optional

// if JSON includes "errorMessages", output them and exit
if ($epics_json['errorMessages'] != '') {
	$errorMessages = $epics_json['errorMessages'];
	foreach ($errorMessages as $errorMessage) {
		echo "\n".'Error: '.$errorMessage."\n\n";
		exit(1);
	}
}

$epics = $epics_json['issues'];

$epics_by_key = Array();
foreach($epics as $epic) {
  $epics_by_key[$epic['key']] = $epic['fields']['summary'];
}

echo ' Done!'."\n";

// ----------------------------------------------------------------
// output sprint plan (optional)
// ----------------------------------------------------------------

$output = '';

if (OUTPUT_SPRINT_PLAN == TRUE) {

	echo 'Generating Sprint Plan as Markdown...';

	$output .= output_sprint_plan($issues, $epics_by_key);		
	
	echo ' Done!'."\n";

}

// ----------------------------------------------------------------
// output user stories
// ----------------------------------------------------------------

echo 'Generating User Stories as Markdown...';

$output .= 'h1. Issues';
$output .= "\n\n";

foreach($issues as $issue) {

	$issue_epic_key = $issue['versionedRepresentations'][EPIC_FIELD][1];
	$issue_epic_name = $epics_by_key[$issue_epic_key];
  $issue_points = $issue['versionedRepresentations'][STORY_POINT_FIELD]['1'];
  
  if (OUTPUT_BOLD_TITLES == TRUE) {
    $output .= 'h2. ';
  }
  $output .= $issue['versionedRepresentations']['summary']['1']."\n\n";
  
  //
  // issue metadata
  //

  if (OUTPUT_STORY_METADATA == TRUE) {
  
	  $output .= '|'.$issue['key'];

	  $output .= '|Type: '.$issue['versionedRepresentations']['issuetype']['1']['name'];
	  
	  if (OUTPUT_STORY_POINTS == TRUE) {
			if ($issue_points <> '') {
	      $output .= '|Points: '.$issue_points;
	    }
	  }
	  
	  if ($issue_epic_name <> '') {				
			$output .= '|Epic: '.$issue_epic_name;
		}
		
		$output .= '|';
		$output .= "\n\n";

	}
  
  //
  // issue content
  //
  
  if ($issue['versionedRepresentations']['description']['1'] != '') {
    $output .= $issue['versionedRepresentations']['description']['1'];
    $output .= "\n\n";
  }
  
  if ($issue['versionedRepresentations'][QUESTION_FIELD]['1'] != '') {
		$output .= '*Questions*';
    $output .= "\n\n";    		
    $output .= $issue['versionedRepresentations'][QUESTION_FIELD]['1'];
    $output .= "\n\n";
  }
   
  $output .= '---------------';
  $output .= "\n\n";
    
}

file_put_contents('out_jira.md', $output);

echo ' Done!'."\n";

// -------------------------------------------------------------
// convert the markdown to doc and txt
// -------------------------------------------------------------

echo 'Converting Markdown to Word...';
$pandoc_cmd = 'pandoc out_jira.md -f textile -t docx --reference-docx jira2doc_reference.docx -o out_doc.docx';
exec($pandoc_cmd, $exec_return);
echo ' Done!'."\n";

echo 'Converting Markdown to Plain Text...';
$pandoc_cmd = 'pandoc out_jira.md -f textile -t plain -o out_jira.txt';
exec($pandoc_cmd, $exec_return);
echo ' Done!'."\n";

exit(0);

// ----------------------------------------------------------------
// FUNCTIONS
// ----------------------------------------------------------------

/**
 * Determines if a command exists on the current environment.
 *
 * @param string $command The command to check
 * @return bool True if the command has been found; otherwise, false.
 */
function command_exists($command) {

  $whereIsCommand = (PHP_OS == 'WINNT') ? 'where' : 'which';

	$process = proc_open(
		"$whereIsCommand $command",
		array(
			0 => array("pipe", "r"), //STDIN
			1 => array("pipe", "w"), //STDOUT
			2 => array("pipe", "w"), //STDERR
		),
		$pipes
	);
	
	if ($process !== false) {
		$stdout = stream_get_contents($pipes[1]);
		$stderr = stream_get_contents($pipes[2]);
		fclose($pipes[1]);
		fclose($pipes[2]);
		proc_close($process);

		return $stdout != '';
	}

  return false;

}

/**
 * Use CURL to get JSON from the given URL.
 */
function curl_get_json($url, $username, $password) {

	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
	curl_setopt($curl, CURLOPT_USERPWD, $username . ":" . $password);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	$json_string = curl_exec($curl);
	curl_close($curl);

	return $json_string;

}

/**
 * Generates Sprint Plan output.
 */
function output_sprint_plan($issues, $epics_by_key) {

	$currentSprint = "";
	$currentEpic = "";

	$output .= 'h1. Sprint Plan';
	$output .= "\n\n";

	foreach($issues as $issue) {

		// TODO: check if story is in an epic <- not sure what this is suggesting?

		$issue_epic_key = $issue['versionedRepresentations'][EPIC_FIELD][1];
		$issue_epic_name = $epics_by_key[$issue_epic_key];

		// TODO: group sub-tasks visually under issue?
		// don't display sub-tasks in this list
		if ($issue['versionedRepresentations']['issuetype']['1']['subtask']) {
			continue;
		}

		// -------------------------------------------------------------
		// output issues in each sprint

		// issue could be in more than one sprint: just consider active and future sprints
		// (below assumes JIRA puts issue in only one active/future sprint)
		$issueSprints = $issue['versionedRepresentations'][SPRINT_FIELD][2];
		$issueSprint = '';
		foreach ($issueSprints as $sprint) {
			if ($sprint['state'] == 'active' || $sprint['state'] == 'future') {
				$issueSprint = $sprint['name'];
				break;
			}
		}
		// if all sprints this issue is in are closed, then skip issue
		if ($issueSprint == '') {
			continue;
		}

		if ($currentSprint !== $issueSprint) {
			if ($currentSprint != "") {
				$output .= "\n\n";
			}
			$currentSprint = $issueSprint;
			$output .= 'h2. '.$currentSprint;
			$currentEpic = "";
			$output .= "\n\n";
		}

		// -------------------------------------------------------------
		// output epics in each sprint

		// UNCOMMENT TO OUTPUT EPIC NAMES, if we want to show epics 
		// TODO: check if story is in epic
		// if ($currentEpic !== $issue_epic_name) {
		// 		$currentEpic = $issue_epic_name;	
		// 		$output .= '* '.$issue_epic_name;
		// 		$output .= "\n\n";
		// }

		$output .= '* '.$issue['key'].' - '.$issue['versionedRepresentations']['summary']['1']."\n";

	}

	$output .= "\n";

	return $output;

}
?>