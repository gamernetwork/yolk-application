# Controllers

Controllers are responsible for processing requests and generating responses;
usually this entails a variation of the following:

* Receive the `Request` from the `Dispatcher`
* Access the service/data layer to perform actions or retrieve data
* Inject data and/or action outcomes into a `View`
* Generate and return a `Response` based on the `View`

Based on the routes we defined in the [Application]('20-application.md') our
`BlogController` might look like:

```php
namespace myapp\controllers;

use yolk\contracts\app\Request;

use yolk\application\BaseController;

class BlogController extends BaseController {

	protected $db;

	public function __construct( ServiceContainer $services ) {

		parent::__construct($services);

		$this->db = $services['db.main'];

	}

	/**
	 * Handles the default "/" route.
	 * @param  Request $request The incoming request to process
	 * @return Response
	 */
	public function homepage( Request $request  ) {

		// fetch the latest 10 blog posts
		$posts = $this->db
			->select()
			->cols('*')
			->from('blog')
			->orderBy('published', false)
			->limit(10)
			->fetchAssoc();

		// return a Response constructed using the 'homepage' view supplied with
		// the list of latest posts
		return $this->respondView(
			'homepage',
			[
				'request' => $request,
				'posts'   => $posts,
			]
		);

	}

	/**
	 * Handles the "/posts/([\d]+{4})/(.*)$" route.
	 * @param  Request $request The incoming request to process
	 * @param  integer $year    The year the post was published.
	 * @param  string  $slug    The slug of the post
	 * @return Response
	 */
	public function viewPost( Request $request, $year, $slug ) {

		// fetch the requested blog post
		$post = $this->db
			->select()
			->cols('*')
			->from('blog')
			->where('slug', $slug)
			->whereRaw("YEAR(published) = :year", ['year' => (int) $year])
			->fetchRow();

		if( empty($post) )
			throw new NotFoundException("Post Not Found: {$year}/{$slug}");

		return $this->respondView(
			'view-post',
			[
			  'request' => $request,
				'post' => $post,
			]
		);

	}

}
```

The admin controller for listing and editing posts might look like:

```php
namespace myapp\controllers;

use yolk\contracts\app\Request;

use yolk\application\BaseController;

class AdminController extends BaseController {

	protected $db;

	public function __construct( ServiceContainer $services ) {

		parent::__construct($services);

		$this->db = $services['db.main'];

	}

	/**
	 * Handles the dashboard "/admin" route.
	 * @param  Request $request The incoming request to process
	 * @return Response
	 */
	public function dashboard( Request $request ) {

		$page = max(1, (int) $request->option('page', 1));

		$posts = $this->db
			->select()
			->cols('*')
			->from('blog')
			->orderBy('published', false)
			->offset(($page - 1) * 10)
			->limit(10)
			->fetchAssoc();

		return $this->respondView(
			'homepage',
			[
				'request' => $request,
				'page'    => $page,
				'posts'   => $posts,
			]
		);

	}

	/**
	 * Handles the "GET:/admin/edit" route.
	 * @param  Request $request The incoming request to process
	 * @return Response
	 */
	public function editPost( Request $request, $id = 0 ) {

    // no id so we're creating a new one
    if( empty($id)) {
      $post = [];
    }
    // fetch the requested blog post
    else {
  		$post = $this->db
  			->select()
  			->cols('*')
  			->from('blog')
  			->where('id', id)
  			->fetchRow();

  		if( empty($post) )
  			throw new NotFoundException("Post Not Found: {$year}/{$slug}");
    }

		return $this->respondView(
			'admin/edit-post',
			[
				'request' => $request,
				'post'    => post,
			]
		);

	}

	/**
	 * Handles the "POST:/admin/save" route.
	 * @param  Request $request The incoming request to process
	 * @return Response
	 */
	public function savePost( Request $request ) {

		$data = $request->data();

		// define fieldset
		// validate data against fieldset
		// insert/update db if valid
		// return json response

	}

}
```
