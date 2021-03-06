<?php

namespace App\Test\Domain\Post;

use App\Domain\Exceptions\ValidationException;
use App\Domain\Post\Post;
use App\Domain\Post\PostService;
use App\Domain\User\UserService;
use App\Domain\Utility\ArrayReader;
use App\Infrastructure\Post\PostRepository;
use App\Infrastructure\User\UserRepository;
use App\Test\UnitTestUtil;
use PHPUnit\Framework\TestCase;

class PostServiceTest extends TestCase
{
    use UnitTestUtil;

    /**
     * Test function findAllPosts from PostService which returns
     * Post array including the name of users
     *
     * @dataProvider \App\Test\Domain\Post\PostProvider::oneSetOfMultiplePostsProvider()
     * @param array $posts
     */
    public function testFindAllPosts(array $posts)
    {
        // Add mock class PostRepository to container and define return value for method findAllPosts
        $this->mock(PostRepository::class)->method('findAllPosts')->willReturn($posts);

        // findAllPosts returns posts with the name of the according user
        $postsWithUsersToCompare = $this->populatePostsArrayWithUserForTesting($posts);

        // Here we don't need to specify what the function will do / return since its exactly that
        // which is being tested. So we can take the autowired class instance from the container directly.
        /** @var PostService $postService */
        $service = $this->container->get(PostService::class);

        self::assertEquals($postsWithUsersToCompare, $service->findAllPosts());

    }

    /**
     * Check if findPost() from PostService returns
     * the post coming from the repository
     *
     * @dataProvider \App\Test\Domain\Post\PostProvider::onePostProvider()
     * @param array $post
     */
    public function testFindPost(array $post)
    {
        // Add mock class PostRepository to container and define return value for method findPostById
        // I dont see the necessity of expecting method to be called. If we get the result we want
        // we can let the code free how it returns it (don't want annoying test that fails after slight code change)
        $this->mock(PostRepository::class)->method('findPostById')->willReturn($post);

        // Get an empty class instance from container
        /** @var PostService $postService */
        $service = $this->container->get(PostService::class);

        self::assertEquals($post, $service->findPost($post['id']));
    }

    /**
     * Check if findAllPostsFromUser() from PostService returns
     * the posts coming from the repository AND
     * if the user names are contained in the returned array
     *
     * @dataProvider \App\Test\Domain\Post\PostProvider::oneSetOfMultiplePostsProvider()
     * @param array $posts
     */
    public function testFindAllPostsFromUser(array $posts)
    {
        // Add mock class PostRepository to container and define return value for method findPostById
        // Posts are with different user_ids from provider and logically findAllPostsFromUser has to return
        // posts with the same user_id since they belong to the same user. But this is not the point of the test.
        // The same posts array will be used in the assertions
        $this->mock(PostRepository::class)->method('findAllPostsByUserId')->willReturn($posts);

        // findAllPosts returns posts with the name of the according user so they have to be added here as well
        $postsWithUsersToCompare= $this->populatePostsArrayWithUserForTesting($posts);

        /** @var PostService $postService */
        $service = $this->container->get(PostService::class);

        // User id not relevant because return values from repo is defined above
        self::assertEquals($postsWithUsersToCompare, $service->findAllPostsFromUser(1));
    }

    /**
     * Test that service method createPost() calls PostRepository:insertPost()
     * and that (service) createPost() returns the id returned from (repo) insertPost()
     *
     * @dataProvider \App\Test\Domain\Post\PostProvider::onePostProvider()
     * @param array $validPost
     */
    public function testCreatePost(array $validPost)
    {
        // Return type of PostRepository:insertPost is string
        $postId = (string)$validPost['id'];

        // Removing id from post array because before post is created id is not known
        unset($validPost['id']);

        // Mock the required repository and configure relevant method return value
        // Here I find ->expects() relevant since the test is about if the method is called or not
        // but should the expected parameter be tested as well? ->with($this->equalTo($validPost)) not included
        // because I dont want an annoying test function that fails for nothing if code changes. Didn't see the
        // real need for a test but maybe I'm wrong.
        $this->mock(PostRepository::class)->expects(self::once())->method('insertPost')->willReturn($postId);

        // Mock UserRepository because it is used in the validation logic.
        $this->mock(UserRepository::class)->method('userExists')->willReturn(true);

        /** @var PostService $postService */
        $postService = $this->container->get(PostService::class);

        $postObj = new Post(new ArrayReader($validPost));

        self::assertEquals($postId, $postService->createPost($postObj));
    }

    /**
     * Test that no post is created when values are invalid.
     * validatePostCreationOrUpdate() will be tested separately but
     * here it is ensured that this validation is called in createUser
     * but without specific error analysis. Important is that it didn't create it.
     * The method is called with each value of the provider
     *
     * @dataProvider \App\Test\Domain\Post\PostProvider::invalidPostsProvider()
     * @param array $invalidPost
     */
    public function testInvalidCreatePost(array $invalidPost)
    {
        // Mock UserRepository because it is used by the validation logic.
        // Empty mock would do the trick as well as it would just return null on non defined functions.
        // A post is linked to an user in all cases so user has to exist. What happens if user doesn't exist
        // will be tested in a different function otherwise this test would always fail and other invalid
        // values would not be noticed
        $this->mock(UserRepository::class)->method('userExists')->willReturn(true);

        /** @var PostService $service */
        $service = $this->container->get(PostService::class);

        $this->expectException(ValidationException::class);

        $service->createPost(new Post(new ArrayReader($invalidPost)));
        // If we wanted to test more detailed, the error messages could be tested, that the right message(s) appear
    }

    /**
     * Test createPost when user doesn't exist
     *
     * @dataProvider \App\Test\Domain\Post\PostProvider::onePostProvider()
     * @param array $validPost
     */
    public function testNotExistingUserCreatePost(array $validPost)
    {
        // Point of this test is not existing user
        $this->mock(UserRepository::class)->method('userExists')->willReturn(false);

        /** @var PostService $service */
        $service = $this->container->get(PostService::class);

        $this->expectException(ValidationException::class);

        $service->createPost(new Post(new ArrayReader($validPost)));
    }

    /**
     * Test that service method updatePost() calls PostRepository:updatePost()
     * and that (service) updatePost() returns the bool true returned by repo
     *
     * Invalid or not existing user don't have to be tested since it's the same
     * validation as createUser() and it's already done there
     *
     * @dataProvider \App\Test\Domain\Post\PostProvider::onePostProvider()
     * @param array $validPost
     */
    public function testUpdatePost(array $validPost)
    {
        // With ->expects() to test if the method is called
        $this->mock(PostRepository::class)->expects(self::once())->method('updatePost')->willReturn(true);

        /** @var PostService $service */
        $service = $this->container->get(PostService::class);

        self::assertTrue($service->updatePost(new Post(new ArrayReader($validPost))));
    }

    /**
     * Test that postRepository:deletePost() is called in
     * post service
     */
    public function testDeletePost()
    {
        $postId = 1;

        $this->mock(PostRepository::class)
            ->expects(self::once())
            ->method('deletePost')
            // With parameter user id
            ->with(self::equalTo($postId))
            ->willReturn(true);

        /** @var PostService $service */
        $service = $this->container->get(PostService::class);

        self::assertTrue($service->deletePost($postId));
    }


    /**
     * Replica of PostService:populatePostsArrayWithUser
     *
     * User not added in PostProvider because only one provider is possible
     * and we need both one array of posts without users (to simulate
     * what comes from the repo) and one with users to assert
     *
     * Not needed to test PostService:populatePostsArrayWithUser because if that function
     * screws up testFindAllPosts and other tests which use that function indirectly will
     * fail since the user is in the expected result of the assert
     *
     * @param array $posts
     * @return array
     */
    private function populatePostsArrayWithUserForTesting(array $posts): array
    {
        // Only the name is relevant for the private function PostService:populatePostsArrayWithUser()
        $userName = 'John Example';
        $this->mock(UserService::class)->method('findUser')
            ->willReturn(['name' => $userName]);

        // Add name of user to posts array
        $postsWithUsersToCompare = [];
        foreach ($posts as $post){
            $post['user_name'] = $userName;
            $postsWithUsersToCompare[] = $post;
        }
        return $postsWithUsersToCompare;
    }
}
