# JIRA2DOC

JIRA to Word Doc, Markdown, Text converter. YMMV.

Executes a query on JIRA, and downloads the results for conversion.

## Installation

This tool requires:

* PHP
* pandoc - Download from <http://pandoc.org/>

## Usage

1. Modify `_jiraenv.php` to point to your JIRA instance, and to the right custom fields.
1. Export your JIRA credentials to the JUSER and JPW environment variables
   `export JUSER=user@company.com`
1. `php jira2doc.php -p JIRA_PROJECT_KEY [-j "JQL"]`
    * JQL: Double-quotes in JQL must be escaped, or you may use single quotes.
      * Default query: `"Project = JIRA_PROJECT_KEY AND Type in (Story, Task) AND Status NOT IN (Done) ORDER BY Sprint, Rank"`