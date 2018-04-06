<?
	const JIRA_BASE_URL = 'https://YOUR_JIRA_SERVER/rest/api/2/search/';

	const OUTPUT_SPRINT_PLAN	= TRUE;
	const OUTPUT_BOLD_TITLES	= TRUE; // setting to FALSE allows copy/paste in to JIRA easier

	const OUTPUT_STORY_METADATA	= FALSE;	// overrides items below if FALSE
	const OUTPUT_STORY_POINTS		= TRUE; 	// set FALSE if not needed for some stakeholders

	const STORY_POINT_FIELD = 'customfield_10005';
	const SPRINT_FIELD      = 'customfield_10007';
	const QUESTION_FIELD    = 'customfield_10300';
	const EPIC_FIELD        = 'customfield_10009';

	// set to a large number if you need everything from JIRA
	const JIRA_RESULTS = '500';

	const JIRA_EPIC_FIELDS = 
		'key,summary,description';
	const JIRA_EPIC_URL = 
		JIRA_BASE_URL.'?fields='.JIRA_EPIC_FIELDS.'&maxResults='.JIRA_RESULTS;

	const JIRA_FIELDS = 
		'key,issuetype,summary,description,'.
		STORY_POINT_FIELD.','.SPRINT_FIELD.','.EPIC_FIELD.','.QUESTION_FIELD;
	const JIRA_BASE_JQL = 
		JIRA_BASE_URL.'?expand=versionedRepresentations&fields='.JIRA_FIELDS.'&maxResults='.JIRA_RESULTS;

	const JIRA_DEFAULT_JQL =
		'Project = [PROJECT_KEY] AND Type in (Story, Task) AND Status NOT IN (Done) ORDER BY Sprint, Rank';
?>