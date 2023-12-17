# jira2md

PHP script that executes a query on Jira, and converts the results to markdown.

Included script can then convert markdown to Word (via Pandoc).

## Installation

This tool requires:

* PHP
* [pandoc](https://pandoc.org) - Optional, to convert markdown to Word.

## Usage

1. Modify `_jiraenv.php` to point to your JIRA instance, and to the right custom fields.
1. Export your JIRA credentials to the JUSER and JPW environment variables
   `export JUSER=user@company.com`
   `export JPW=yourjirapassword`
1. `php jira2doc.php -p JIRA_PROJECT_KEY [-j "JQL"]`
    * JQL: Double-quotes in JQL must be escaped, or you may use single quotes.
      * Default query: `"Project = JIRA_PROJECT_KEY AND Type in (Story, Task) AND Status NOT IN (Done) ORDER BY Sprint, Rank"`