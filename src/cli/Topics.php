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
        $topics = models\Topic::listAll();
        $presented_topics = [];
        foreach ($topics as $topic) {
            $presented_topics[] = $topic->id . ' ' . $topic->label;
        }

        return Response::text(200, implode("\n", $presented_topics));
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
        $label = $request->param('label');
        $topic = models\Topic::init($label);

        $errors = $topic->validate();
        if ($errors) {
            $errors = implode(' ', $errors);
            return Response::text(400, "Topic creation failed: {$errors}");
        }

        $topic->save();

        return Response::text(200, "Topic {$topic->label} ({$topic->id}) has been created.");
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
        $id = $request->param('id');

        $topic = models\Topic::find($id);
        if (!$topic) {
            return Response::text(404, "Topic id `{$id}` does not exist.");
        }

        models\Topic::delete($topic->id);

        return Response::text(200, "Topic {$topic->label} ({$topic->id}) has been deleted.");
    }
}
