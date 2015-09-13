<?php

namespace Pagekit\Portfolio\Controller;

use Pagekit\Application as App;
use Pagekit\Module\Module;
use Pagekit\Portfolio\Model\Project;

class SiteController
{
    /**
     * @var Module
     */
    protected $portfolio;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->portfolio = App::module('portfolio');
    }

    /**
     * @Route("/")
     */
    public function indexAction()
    {
        if (!App::node()->hasAccess(App::user())) {
            App::abort(403, __('Insufficient User Rights.'));
        }

        $query = Project::where(['date < ?'], [new \DateTime])->orderBy('date', 'DESC');

		$portfolio_text = '';
		if ($this->portfolio->config('portfolio_text')) {
			$portfolio_text = App::content()->applyPlugins($this->portfolio->config('portfolio_text'), ['markdown' => $this->portfolio->config('markdown_enabled')]);;
		}

		foreach ($projects = $query->get() as $project) {
			$project->intro = App::content()->applyPlugins($project->intro, ['project' => $project, 'markdown' => $project->get('markdown')]);
			$project->content = App::content()->applyPlugins($project->content, ['project' => $project, 'markdown' => $project->get('markdown'), 'readmore' => true]);
        }

        return [
            '$view' => [
                'title' => $this->portfolio->config('portfolio_title') ?: App::node()->title,
                'name' => 'portfolio/portfolio.php'
            ],
			'tags' => Project::allTags(),
      		'portfolio' => $this->portfolio,
			'config' => $this->portfolio->config(),
			'portfolio_text' => $portfolio_text,
            'projects' => $projects
        ];
    }

    /**
     * @Route("/{id}", name="id")
     */
    public function projectAction($id = 0)
    {
        if (!$project = Project::where(['id = ?', 'date < ?'], [$id, new \DateTime])->first()) {
            App::abort(404, __('Project not found.'));
        }

        $project->intro = App::content()->applyPlugins($project->intro, ['project' => $project, 'markdown' => $project->get('markdown')]);
        $project->content = App::content()->applyPlugins($project->content, ['project' => $project, 'markdown' => $project->get('markdown')]);

		$portfolio_text = '';
		if ($this->portfolio->config('portfolio_text')) {
			$portfolio_text = App::content()->applyPlugins($this->portfolio->config('portfolio_text'), ['markdown' => $project->get('markdown')]);;
		}
        return [
            '$view' => [
                'title' => __($project->title),
                'name' => 'portfolio/project.php'
            ],
            'portfolio' => $this->portfolio,
			'config' => $this->portfolio->config(),
			'project' => $project
        ];
    }
}
