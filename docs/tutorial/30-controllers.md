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
use yolk\app\BaseController;
use yolk\app\exceptions\NotFoundException;

class BlogController extends BaseController {

	protected $db;

	public function __construct( ServiceContainer $services ) {

		parent::__construct($services);

		// we get the main database connection and store it in a class property
		// for ease of access
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
				// passing the request to the view is usually required
				// so the view has access to the current uri, request data, etc
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

		// if the post can't be found we throw a NotFoundException
		// the application will then convert that into a "404 Not Found" response
		if( empty($post) )
			throw new NotFoundException("Post Not Found: {$year}/{$slug}");

		return $this->respondView(
			'view-post',
			[
				'request' => $request,
				'post'    => $post,
			]
		);

	}

}
```

The admin controller for listing and editing posts might look like:

```php
namespace myapp\controllers;

use yolk\contracts\app\Request;
use yolk\app\BaseController;
use yolk\app\exceptions\NotFoundException;

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

		// a page number can be passed via the query string
		// we convert it to an integer and ensure the minimum
		// value is 1
		$page = max(1, (int) $request->option('page', 1));

		// fetch the items for the requested page
		$posts = $this->db
			->select()
			->cols('*')
			->from('posts')
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
				->from('posts')
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
	public function savePost( Request $request, $id = 0 ) {

		// we define a Fieldset to describe the edit form structure
		$fieldset = new Fieldset();
		$fieldset->add('title',     Type::TEXT, ['required' => true]);
		$fieldset->add('slug',      Type::TEXT, ['required' => true]);
		$fieldset->add('body',      Type::TEXT, ['required' => true]);

		// grab the request data and fill in any missing values with empty strings
		$data = $request->data() + array_fill_keys($fieldset->listNames(), '');

		// validate the data against the defined Fieldset
		list($post, $errors) = $fieldset->validate($data);

		// there's been errors so let's return them as a JSON response
		if( !empty($errors) ) {
			return $this->respondJSON([
				'success' => false,
				'errors'  => $errors,
			]);
		}

		// no id so it's a new post
		if( empty($id) ) {
			$this->db
				->insert()
				->into('posts')
				->item($data)
				->execute();
			$id = $this->db->insertId();
		}
		// existing post so update
		else {
			$this->db
				->update()
				->table('posts')
				->set($data)
				->where('id', $id)
				->execute();
		}

		// return some json indicating a successful operation and the post id
		return $this->respondJSON([
			'success' => true,
			'id'      => $id,
		])

	}

}
```
