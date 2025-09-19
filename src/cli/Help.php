<?php

namespace App\cli;

use Minz\Response;
use App\models;

/**
 * Display help message.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Help
{
    /**
     * @response 200
     */
    public function show(): Response
    {
        $topic_label_max_size = models\Topic::LABEL_MAX_SIZE;

        $usage = "Usage: php cli COMMAND [--OPTION=VALUE]...\n";
        $usage .= "\n";
        $usage .= "COMMAND can be one of the following:\n";
        $usage .= "  help                     Show this help\n";
        $usage .= "\n";
        $usage .= "  database status          Return the status of the DB connection\n";
        $usage .= "\n";
        $usage .= "  features                 List the available features types\n";
        $usage .= "  features flags           List the enabled feature flags\n";
        $usage .= "  features enable          Enable a feature flag for a user\n";
        $usage .= "      --type=TEXT          where TEXT is the feature flag type\n";
        $usage .= "      --user_id=ID         where ID is the user’s id\n";
        $usage .= "  features disable         Disable a feature flag for a user\n";
        $usage .= "      --type=TEXT          where TEXT is the feature flag type\n";
        $usage .= "      --user_id=ID         where ID is the user’s id\n";
        $usage .= "\n";
        $usage .= "  feeds                    List the feeds\n";
        $usage .= "  feeds add                Add a feed\n";
        $usage .= "      --url=URL            where URL is the link to the feed\n";
        $usage .= "  feeds reset-hashes       Reset the hashes of all the feeds (allow sync of unchanged feeds)\n";
        $usage .= "  feeds sync               Synchronize a feed\n";
        $usage .= "      --id=ID              where ID is the id of the feed\n";
        $usage .= "      [--nocache=BOOL]     Indicates if the cache should be ignored (default is false)\n";
        $usage .= "\n";
        $usage .= "  jobs                     List the registered jobs\n";
        $usage .= "  jobs install             (Re-)install the jobs (to run after a configuration change)\n";
        $usage .= "  jobs watch               Wait for and execute jobs\n";
        $usage .= "      [--queue=TEXT]       The name of the queue to wait (default: all)\n";
        $usage .= "      [--stop-after=INT]   The max number of jobs to execute (default is infinite)\n";
        $usage .= "      [--sleep-duration=INT] The number of seconds between two cycles (default: 3)\n";
        $usage .= "  jobs show                Display info about a job\n";
        $usage .= "      --id=ID              The ID of the job\n";
        $usage .= "  jobs run                 Execute a single job\n";
        $usage .= "      --id=ID              The ID of the job\n";
        $usage .= "  jobs unfail              Discard the error of a job\n";
        $usage .= "      --id=ID              The ID of the job\n";
        $usage .= "  jobs unlock              Unlock a job\n";
        $usage .= "      --id=ID              The ID of the job\n";
        $usage .= "\n";
        $usage .= "  media clean              Clean the unused media (it may take a VERY long time to finish)\n";
        $usage .= "\n";
        $usage .= "  migrations               List the migrations\n";
        $usage .= "  migrations setup         Initialize or migrate the application\n";
        $usage .= "      [--seed=BOOL]        Whether you want to seed the application or not (default: false)\n";
        $usage .= "  migrations rollback      Rollback the latest migrations\n";
        $usage .= "      [--steps=INT]        The number of migrations to rollback\n";
        $usage .= "  migrations create        Create a new migration\n";
        $usage .= "      --name=TEXT          The name of the migration (only chars from A to Z and numbers)\n";
        $usage .= "\n";
        $usage .= "  system                   Show information about the system\n";
        $usage .= "  system secret            Generate a secure key to be used as APP_SECRET_KEY\n";
        $usage .= "  system stats             Show statistics about the system\n";
        $usage .= "      [--format=TEXT]      where TEXT is either `csv` or `plain` (default)\n";
        $usage .= "      [--year=INT]         where INT is the year to output (only for CSV format)\n";
        $usage .= "\n";
        $usage .= "  topics                   List the topics\n";
        $usage .= "  topics create            Create a topic\n";
        $usage .= "      --label=TEXT         where TEXT is a {$topic_label_max_size}-chars max string\n";
        $usage .= "      [--image_url=URL]    where URL is an optional illustration image\n";
        $usage .= "  topics delete            Delete a topic\n";
        $usage .= "      --id=ID              where ID is the id of the topic to delete\n";
        $usage .= "  topics update            Update a topic\n";
        $usage .= "      --id=ID              where ID is the id of the topic to delete\n";
        $usage .= "      [--label=TEXT]       where TEXT is a {$topic_label_max_size}-chars max string\n";
        $usage .= "      [--image_url=URL]    where URL is an optional illustration image\n";
        $usage .= "\n";
        $usage .= "  urls show                Show the HTTP response returned by an URL\n";
        $usage .= "      --url=TEXT           where TEXT is an external URL\n";
        $usage .= "      [--user-agent=TEXT]  where TEXT is an optional User-Agent\n";
        $usage .= "  urls uncache             Clear the cache of the given URL\n";
        $usage .= "      --url=TEXT           where TEXT is an external URL\n";
        $usage .= "\n";
        $usage .= "  users                    List all the users\n";
        $usage .= "      [--to-contact=BOOL]  list only the users who accepted to be contacted (default: false)\n";
        $usage .= "  users create             Create a user\n";
        $usage .= "      --email=EMAIL\n";
        $usage .= "      --password=PASSWORD\n";
        $usage .= "      --username=USERNAME  where USERNAME is a 50-chars max string\n";
        $usage .= "  users export             Export the data of the given user in the current directory\n";
        $usage .= "      --id=ID              where ID is the id of the user to export\n";
        $usage .= "  users validate           Validate a user account\n";
        $usage .= "      --id=ID              where ID is the id of the user to validate\n";

        return Response::text(200, $usage);
    }
}
