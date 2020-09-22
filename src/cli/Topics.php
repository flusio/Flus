<?php

namespace flusio\cli;

use Minz\Response;
use flusio\models;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Topics
{
    /**
     * List all the topics
     *
     * @response 200
     *
     * @param \Minz\Request $request
     *
     * @return \Minz\Response
     */
    public function index($request)
    {
        $topic_dao = new models\dao\Topic();
        $db_topics = $topic_dao->listAll();

        $topics = [];
        foreach ($db_topics as $db_topic) {
            $topics[] = $db_topic['id'] . ' ' . $db_topic['label'];
        }

        return Response::text(200, implode("\n", $topics));
    }

    /**
     * Create a topic.
     *
     * @request_param label
     *
     * @response 400 if one of the param is invalid
     * @response 200
     *
     * @param \Minz\Request $request
     *
     * @return \Minz\Response
     */
    public function create($request)
    {
        $topic_dao = new models\dao\Topic();
        $label = $request->param('label');

        $topic = models\Topic::init($label);

        $errors = $topic->validate();
        if ($errors) {
            $errors = implode(' ', $errors);
            return Response::text(400, "Topic creation failed: {$errors}");
        }

        $topic_id = $topic_dao->save($topic);

        return Response::text(200, "Topic {$topic->label} ({$topic_id}) has been created.");
    }

    /**
     * Delete a topic.
     *
     * @request_param id
     *
     * @response 404 if the id doesn't exist
     * @response 200
     *
     * @param \Minz\Request $request
     *
     * @return \Minz\Response
     */
    public function delete($request)
    {
        $topic_dao = new models\dao\Topic();
        $id = $request->param('id');

        $db_topic = $topic_dao->find($id);
        if (!$db_topic) {
            return Response::text(404, "Topic id `{$id}` does not exist.");
        }

        $topic = new models\Topic($db_topic);
        $topic_dao->delete($topic->id);

        return Response::text(200, "Topic {$topic->label} ({$topic->id}) has been deleted.");
    }
}
