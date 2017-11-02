<?php

/**
 * Author: Nil Portugués Calderó <contact@nilportugues.com>
 * Date: 7/18/15
 * Time: 11:27 AM.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace NilPortugues\Tests\JsonApi;

use NilPortugues\Api\JsonApi\Http\Request\Parameters\Fields;
use NilPortugues\Api\JsonApi\Http\Request\Parameters\Included;
use NilPortugues\Api\JsonApi\JsonApiSerializer;
use NilPortugues\Api\JsonApi\JsonApiTransformer;
use NilPortugues\Api\Mapping\Mapper;
use NilPortugues\Api\Mapping\Mapping;
use NilPortugues\Api\Transformer\TransformerException;
use NilPortugues\Tests\Api\JsonApi\Behaviour\Dummy\SimpleObject\Comment as SimpleComment;
use NilPortugues\Tests\Api\JsonApi\Behaviour\Dummy\SimpleObject\Post as SimplePost;
use NilPortugues\Tests\Api\JsonApi\Behaviour\HelperFactory;
use NilPortugues\Tests\Api\JsonApi\Behaviour\HelperMapping;

class JsonApiTransformerTest extends \PHPUnit_Framework_TestCase
{
    public function testItIfFilteringOutKeys()
    {
        $post = HelperFactory::simplePost();

        $postMapping = new Mapping(SimplePost::class, '/post/{postId}', ['postId']);
        $postMapping->setProperties(['postId', 'title', 'body', 'authorId', 'comments']);
        $postMapping->setFilterKeys(['body']);

        $mapper = new Mapper();
        $mapper->setClassMap([$postMapping->getClassName() => $postMapping]);

        $jsonApiJsonApiSerializer = new JsonApiTransformer($mapper);

        $expected = <<<JSON
{
    "data": {
        "type": "post",
        "id": "1",
        "attributes": {    
            "body": "post body"
        },
        "links": {
            "self": { "href": "/post/1"}
        }
    },
    "links": {
        "self": { "href": "/post/1" }
    },
    "jsonapi": {
        "version": "1.0"
    }
}
JSON;

        $this->assertEquals(
            \json_decode($expected, true),
            \json_decode((new JsonApiSerializer($jsonApiJsonApiSerializer))->serialize($post), true)
        );
    }

    /**
     *
     */
    public function testItWillSerializeToJsonApiAComplexObject()
    {
        $mappings = HelperMapping::complex();
        $mapper = new Mapper($mappings);

        $expected = <<<JSON
{
   "data":{
      "type":"post",
      "id":"9",
      "attributes":{
         "title":"Hello World",
         "content":"Your first post",
         "post_id": 9
      },
      "links":{
         "self":{
            "href":"http://example.com/posts/9"
         },
         "comments":{
            "href":"http://example.com/posts/9/comments"
         }
      },
      "relationships":{
         "author":{
            "links":{
               "self":{
                  "href":"http://example.com/posts/9/relationships/author"
               },
               "related":{
                  "href":"http://example.com/posts/9/author"
               }
            },
            "data":{
               "type":"user",
               "id":"1"
            }
         },
         "comments":{
            "data":[
               {
                  "type":"comment",
                  "id":"1000"
               }
            ]
         }
      }
   },
   "included":[
      {
         "type":"user",
         "id":"1",
         "attributes":{
            "name":"Post Author"
         },
         "links":{
            "self":{
               "href":"http://example.com/users/1"
            },
            "friends":{
               "href":"http://example.com/users/1/friends"
            },
            "comments":{
               "href":"http://example.com/users/1/comments"
            }
         }
      },
      {
         "type":"user",
         "id":"3",
         "attributes":{
            "name":"First Liker"
         },
         "links":{
            "self":{
               "href":"http://example.com/users/3"
            },
            "friends":{
               "href":"http://example.com/users/3/friends"
            },
            "comments":{
               "href":"http://example.com/users/3/comments"
            }
         }
      },
      {
         "type":"user",
         "id":"4",
         "attributes":{
            "name":"Second Liker"
         },
         "links":{
            "self":{
               "href":"http://example.com/users/4"
            },
            "friends":{
               "href":"http://example.com/users/4/friends"
            },
            "comments":{
               "href":"http://example.com/users/4/comments"
            }
         }
      },
      {
         "type":"user",
         "id":"2",
         "attributes":{
            "name":"Barristan Selmy"
         },
         "links":{
            "self":{
               "href":"http://example.com/users/2"
            },
            "friends":{
               "href":"http://example.com/users/2/friends"
            },
            "comments":{
               "href":"http://example.com/users/2/comments"
            }
         }
      },
      {
         "type":"comment",
         "id":"1000",
         "attributes":{
            "dates":{
               "created_at":"2015-07-18T12:13:00+00:00",
               "accepted_at":"2015-07-19T00:00:00+00:00"
            },
            "comment":"Have no fear, sers, your king is safe.",
            "one_date" : {
                "date" : "2015-07-18 12:13:00.000000",
                "timezone_type": 1,
                "timezone" : "+00:00"
            }
         },
         "relationships":{
            "user":{
               "data":{
                  "type":"user",
                  "id":"2"
               }
             },
            "likes": {
               "data": [
                  { 
                    "type": "user",
                    "id":"3"
                  },
                  {  
                    "type": "user",
                    "id":"4"
                  }
                ]
            }
         },
         "links":{
            "self":{
               "href":"http://example.com/comments/1000"
            }
         }
      }
   ],
   "links":{
      "comments":{
         "href":"http://example.com/posts/9/comments"
      },
      "self":{
         "href":"http://example.com/posts/9"
      }
   },
   "meta":{
      "author":{
         "name":"Nil Portugués Calderó",
         "email":"contact@nilportugues.com"
      },
      "is_devel":true
   },
   "jsonapi":{
      "version":"1.0"
   }
}
JSON;
        $post = HelperFactory::complexPost();

        $transformer = new JsonApiTransformer($mapper);
        $transformer->setMeta(
            [
                'author' => [
                    'name' => 'Nil Portugués Calderó',
                    'email' => 'contact@nilportugues.com',
                ],
            ]
        );
        $transformer->addMeta('is_devel', true);

        $this->assertEquals(
            \json_decode($expected, true),
            \json_decode((new JsonApiSerializer($transformer))->serialize($post), true)
        );
    }

    public function testGetTransformerReturnsJsonApiTransformer()
    {
        $serializer = new JsonApiSerializer(new JsonApiTransformer(new Mapper(HelperMapping::complex())));

        $this->assertInstanceOf(JsonApiTransformer::class, $serializer->getTransformer());
    }

    /**
     *
     */
    public function testItWillThrowExceptionIfNoMappingsAreProvided()
    {
        $mapper = new Mapper();
        $mapper->setClassMap([]);

        $this->setExpectedException(TransformerException::class);
        (new JsonApiSerializer(new JsonApiTransformer($mapper)))->serialize(new \stdClass());
    }

    /**
     *
     */
    public function testItWillSerializeToJsonApiAComplexObjectAndFilterIncluded()
    {
        $mappings = HelperMapping::complex();
        $mapper = new Mapper($mappings);

        $expected = <<<JSON
{
   "data":{
      "type":"post",
      "id":"9",
      "attributes":{
         "title":"Hello World",
         "content":"Your first post",
         "post_id": 9
      },
      "links":{
         "self":{
            "href":"http://example.com/posts/9"
         },
         "comments":{
            "href":"http://example.com/posts/9/comments"
         }
      },
      "relationships":{
         "author":{
            "links":{
               "self":{
                  "href":"http://example.com/posts/9/relationships/author"
               },
               "related":{
                  "href":"http://example.com/posts/9/author"
               }
            },
            "data":{
               "type":"user",
               "id":"1"
            }
         }
      }
   },
   "included":[
      {
         "type":"user",
         "id":"1",
         "attributes":{
            "name":"Post Author"
         },
         "links":{
            "self":{
               "href":"http://example.com/users/1"
            },
            "friends":{
               "href":"http://example.com/users/1/friends"
            },
            "comments":{
               "href":"http://example.com/users/1/comments"
            }
         }
      }
   ],
   "links":{
      "self":{
         "href":"http://example.com/posts/9"
      },
      "comments":{
         "href":"http://example.com/posts/9/comments"
      }
   },
   "meta":{
      "author":{
         "name":"Nil Portugués Calderó",
         "email":"contact@nilportugues.com"
      },
      "is_devel":true
   },
   "jsonapi":{
      "version":"1.0"
   }
}
JSON;
        $post = HelperFactory::complexPost();

        $transformer = new JsonApiTransformer($mapper);
        $transformer->setMeta(
            [
                'author' => [
                    'name' => 'Nil Portugués Calderó',
                    'email' => 'contact@nilportugues.com',
                ],
            ]
        );
        $transformer->addMeta('is_devel', true);

        $included = new Included();
        $included->add('user.post');

        $this->assertEquals(
            \json_decode($expected, true),
            \json_decode((new JsonApiSerializer($transformer))->serialize($post, new Fields(), $included), true)
        );
    }
    /**
     *
     */
    public function testItWillSerializeToJsonApiAComplexObjectAndFilterIncludedSpecificResource()
    {
        $mappings = HelperMapping::complex();
        $mapper = new Mapper($mappings);

        $expected = <<<JSON
{
   "data":{
      "type":"post",
      "id":"9",
      "attributes":{
         "title":"Hello World",
         "content":"Your first post",
         "post_id": 9
      },
      "links":{
         "self":{
            "href":"http://example.com/posts/9"
         },
         "comments":{
            "href":"http://example.com/posts/9/comments"
         }
      },
      "relationships":{
         "author":{
            "links":{
               "self":{
                  "href":"http://example.com/posts/9/relationships/author"
               },
               "related":{
                  "href":"http://example.com/posts/9/author"
               }
            },
            "data":{
               "type":"user",
               "id":"1"
            }
         }
      }
   },
   "included":[
      {
         "type":"user",
         "id":"1",
         "attributes":{
            "name":"Post Author"
         },
         "links":{
            "self":{
               "href":"http://example.com/users/1"
            },
            "friends":{
               "href":"http://example.com/users/1/friends"
            },
            "comments":{
               "href":"http://example.com/users/1/comments"
            }
         }
      }
   ],
   "links":{
      "self":{
         "href":"http://example.com/posts/9"
      },
      "comments":{
         "href":"http://example.com/posts/9/comments"
      }
   },
   "meta":{
      "author":{
         "name":"Nil Portugués Calderó",
         "email":"contact@nilportugues.com"
      },
      "is_devel":true
   },
   "jsonapi":{
      "version":"1.0"
   }
}
JSON;
        $post = HelperFactory::complexPost();

        $transformer = new JsonApiTransformer($mapper);
        $transformer->setMeta(
            [
                'author' => [
                    'name' => 'Nil Portugués Calderó',
                    'email' => 'contact@nilportugues.com',
                ],
            ]
        );
        $transformer->addMeta('is_devel', true);

        $included = new Included();
        $included->add('user');

        $this->assertEquals(
            \json_decode($expected, true),
            \json_decode((new JsonApiSerializer($transformer))->serialize($post, null, $included), true)
        );
    }

    /**
     *
     */
    public function testItWillSerializeToJsonApiAComplexObjectAndFilterFields()
    {
        $mappings = HelperMapping::complex();
        $mapper = new Mapper($mappings);

        $expected = <<<JSON
{
   "data":{
      "type":"post",
      "id":"9",
      "attributes":{
         "title":"Hello World"
      },
      "links":{
         "self":{
            "href":"http://example.com/posts/9"
         },
         "comments":{
            "href":"http://example.com/posts/9/comments"
         }
      }
   },
   "links":{
      "self":{
         "href":"http://example.com/posts/9"
      },
      "comments":{
         "href":"http://example.com/posts/9/comments"
      }
   },
   "meta":{
      "author":{
         "name":"Nil Portugués Calderó",
         "email":"contact@nilportugues.com"
      },
      "is_devel":true
   },
   "jsonapi":{
      "version":"1.0"
   }
}
JSON;
        $post = HelperFactory::complexPost();

        $transformer = new JsonApiTransformer($mapper);
        $transformer->setMeta(
            [
                'author' => [
                    'name' => 'Nil Portugués Calderó',
                    'email' => 'contact@nilportugues.com',
                ],
            ]
        );
        $transformer->addMeta('is_devel', true);

        $fields = new Fields();
        $fields->addField('post', 'title');

        $this->assertEquals(
            \json_decode($expected, true),
            \json_decode((new JsonApiSerializer($transformer))->serialize($post, $fields), true)
        );
    }

    /**
     *
     */
    public function testItWillSerializeToJsonApiASimpleObject()
    {
        $post = HelperFactory::simplePost();

        $postMapping = new Mapping(SimplePost::class, '/post/{postId}', ['postId']);

        $mapper = new Mapper();
        $mapper->setClassMap([$postMapping->getClassName() => $postMapping]);

        $jsonApiJsonApiSerializer = new JsonApiTransformer($mapper);

        $expected = <<<JSON
{
    "data": {
        "type": "post",
        "id": "1",
        "attributes": {
            "post_id": 1,
            "title": "post title",
            "body": "post body",
            "author_id": 2,
            "comments": [
                {
                    "comment_id": 10,
                    "comment": "I am writing comment no. 1",
                    "user_id": "User 5",
                    "created_at": "2015-07-19T12:48:00+02:00"
                },
                {
                    "comment_id": 20,
                    "comment": "I am writing comment no. 2",
                    "user_id": "User 10",
                    "created_at": "2015-07-20T12:48:00+02:00"
                },
                {
                    "comment_id": 30,
                    "comment": "I am writing comment no. 3",
                    "user_id": "User 15",
                    "created_at": "2015-07-21T12:48:00+02:00"
                },
                {
                    "comment_id": 40,
                    "comment": "I am writing comment no. 4",
                    "user_id": "User 20",
                    "created_at": "2015-07-22T12:48:00+02:00"
                },
                {
                    "comment_id": 50,
                    "comment": "I am writing comment no. 5",
                    "user_id": "User 25",
                    "created_at": "2015-07-23T12:48:00+02:00"
                }
            ]
        },
        "links": {
            "self": { "href": "/post/1" }
        }
    },
    "links": {
        "self": { "href": "/post/1" }
    },
    "jsonapi": {
        "version": "1.0"
    }
}
JSON;

        $this->assertEquals(
            \json_decode($expected, true),
            \json_decode((new JsonApiSerializer($jsonApiJsonApiSerializer))->serialize($post), true)
        );
    }

    /**
     *
     */
    public function testItWillRenamePropertiesFromClass()
    {
        $post = HelperFactory::simplePost();

        $postMapping = new Mapping(SimplePost::class, '/post/{postId}', ['postId']);
        $postMapping->setPropertyNameAliases(['title' => 'headline', 'body' => 'post', 'postId' => 'someId']);

        $mapper = new Mapper();
        $mapper->setClassMap([$postMapping->getClassName() => $postMapping]);

        $jsonApiJsonApiSerializer = new JsonApiTransformer($mapper);

        $expected = <<<JSON
{
    "data": {
        "type": "post",
        "id": "1",
        "attributes": {        
            "some_id": 1,
            "headline": "post title",
            "post": "post body",
            "author_id": 2,
            "comments": [
                {
                    "comment_id": 10,
                    "comment": "I am writing comment no. 1",
                    "user_id": "User 5",
                    "created_at": "2015-07-19T12:48:00+02:00"
                },
                {
                    "comment_id": 20,
                    "comment": "I am writing comment no. 2",
                    "user_id": "User 10",
                    "created_at": "2015-07-20T12:48:00+02:00"
                },
                {
                    "comment_id": 30,
                    "comment": "I am writing comment no. 3",
                    "user_id": "User 15",
                    "created_at": "2015-07-21T12:48:00+02:00"
                },
                {
                    "comment_id": 40,
                    "comment": "I am writing comment no. 4",
                    "user_id": "User 20",
                    "created_at": "2015-07-22T12:48:00+02:00"
                },
                {
                    "comment_id": 50,
                    "comment": "I am writing comment no. 5",
                    "user_id": "User 25",
                    "created_at": "2015-07-23T12:48:00+02:00"
                }
            ]
        },
        "links": {
            "self": { "href": "/post/1" }
        }
    },
    "links": {
        "self": { "href": "/post/1" }
    },
    "jsonapi": {
        "version": "1.0"
    }
}
JSON;

        $this->assertEquals(
            \json_decode($expected, true),
            \json_decode((new JsonApiSerializer($jsonApiJsonApiSerializer))->serialize($post), true)
        );
    }

    /**
     *
     */
    public function testItWillHidePropertiesFromClass()
    {
        $post = HelperFactory::simplePost();

        $postMapping = new Mapping(SimplePost::class, '/post/{postId}', ['postId']);
        $postMapping->setHiddenProperties(['title', 'body']);

        $mapper = new Mapper();
        $mapper->setClassMap([$postMapping->getClassName() => $postMapping]);

        $jsonApiJsonApiSerializer = new JsonApiTransformer($mapper);

        $expected = <<<JSON
{
    "data": {
        "type": "post",
        "id": "1",
        "attributes": {        
            "post_id": 1,
            "author_id": 2,
            "comments": [
                {
                    "comment_id": 10,
                    "comment": "I am writing comment no. 1",
                    "user_id": "User 5",
                    "created_at": "2015-07-19T12:48:00+02:00"
                },
                {
                    "comment_id": 20,
                    "comment": "I am writing comment no. 2",
                    "user_id": "User 10",
                    "created_at": "2015-07-20T12:48:00+02:00"
                },
                {
                    "comment_id": 30,
                    "comment": "I am writing comment no. 3",
                    "user_id": "User 15",
                    "created_at": "2015-07-21T12:48:00+02:00"
                },
                {
                    "comment_id": 40,
                    "comment": "I am writing comment no. 4",
                    "user_id": "User 20",
                    "created_at": "2015-07-22T12:48:00+02:00"
                },
                {
                    "comment_id": 50,
                    "comment": "I am writing comment no. 5",
                    "user_id": "User 25",
                    "created_at": "2015-07-23T12:48:00+02:00"
                }
            ]
        },
        "links": {
            "self": { "href": "/post/1" }
        }
    },
    "links": {
        "self": { "href": "/post/1" }
    },
    "jsonapi": {
        "version": "1.0"
    }
}
JSON;

        $this->assertEquals(
            \json_decode($expected, true),
            \json_decode((new JsonApiSerializer($jsonApiJsonApiSerializer))->serialize($post), true)
        );
    }

    public function testTypeValueIsChangedByClassAlias()
    {
        $post = HelperFactory::simplePost();

        $postMapping = new Mapping(SimplePost::class, '/post/{postId}', ['postId']);
        $postMapping->setClassAlias('Message');

        $mapper = new Mapper();
        $mapper->setClassMap([$postMapping->getClassName() => $postMapping]);

        $jsonApiJsonApiSerializer = new JsonApiTransformer($mapper);

        $expected = <<<JSON
{
    "data": {
        "type": "message",
        "id": "1",
        "attributes": {        
            "post_id": 1,
            "title": "post title",
            "body": "post body",
            "author_id": 2,
            "comments": [
                {
                    "comment_id": 10,
                    "comment": "I am writing comment no. 1",
                    "user_id": "User 5",
                    "created_at": "2015-07-19T12:48:00+02:00"
                },
                {
                    "comment_id": 20,
                    "comment": "I am writing comment no. 2",
                    "user_id": "User 10",
                    "created_at": "2015-07-20T12:48:00+02:00"
                },
                {
                    "comment_id": 30,
                    "comment": "I am writing comment no. 3",
                    "user_id": "User 15",
                    "created_at": "2015-07-21T12:48:00+02:00"
                },
                {
                    "comment_id": 40,
                    "comment": "I am writing comment no. 4",
                    "user_id": "User 20",
                    "created_at": "2015-07-22T12:48:00+02:00"
                },
                {
                    "comment_id": 50,
                    "comment": "I am writing comment no. 5",
                    "user_id": "User 25",
                    "created_at": "2015-07-23T12:48:00+02:00"
                }
            ]
        },
        "links": {
            "self": { "href": "/post/1"}
        }
    },
    "links": {
        "self": { "href": "/post/1" }
    },
    "jsonapi": {
        "version": "1.0"
    }
}
JSON;

        $this->assertEquals(
            \json_decode($expected, true),
            \json_decode((new JsonApiSerializer($jsonApiJsonApiSerializer))->serialize($post), true)
        );
    }

    /**
     *
     */
    public function testItWillSerializeToJsonApiAnArrayOfObjects()
    {
        $postArray = [
            new SimplePost(1, 'post title 1', 'post body 1', 4),
            new SimplePost(2, 'post title 2', 'post body 2', 5),
        ];

        $postMapping = new Mapping(SimplePost::class, '/post/{postId}', ['postId']);
        $postMapping->setProperties(['postId', 'title', 'body', 'authorId', 'comments']);
        $postMapping->setFilterKeys(['body', 'title']);

        $mapper = new Mapper();
        $mapper->setClassMap([$postMapping->getClassName() => $postMapping]);

        $jsonApiJsonApiSerializer = new JsonApiTransformer($mapper);
        $jsonApiJsonApiSerializer->setMeta(
            [
                'author' => [
                    'name' => 'Nil Portugués Calderó',
                    'email' => 'contact@nilportugues.com',
                ],
            ]
        );
        $jsonApiJsonApiSerializer->addMeta('is_devel', true);

        $expected = <<<JSON
{
   "data":[
      {
         "type":"post",
         "id":"1",
         "attributes":{
            "title":"post title 1",
            "body":"post body 1"
         },
         "links":{
            "self":{
               "href":"/post/1"
            }
         }
      },
      {
         "type":"post",
         "id":"2",
         "attributes":{
            "title":"post title 2",
            "body":"post body 2"
         },
         "links":{
            "self":{
               "href":"/post/2"
            }
         }
      }
   ],
   "meta":{
      "author":{
         "name":"Nil Portugués Calderó",
         "email":"contact@nilportugues.com"
      },
      "is_devel":true
   },
   "jsonapi":{
      "version":"1.0"
   }
}
JSON;

        $this->assertEquals(
            \json_decode($expected, true),
            \json_decode((new JsonApiSerializer($jsonApiJsonApiSerializer))->serialize($postArray), true)
        );
    }

    /**
     *
     */
    public function testItWillBuildUrlUsingAliasOrTypeNameIfIdFieldNotPresentInUrl()
    {
        $post = HelperFactory::simplePost();

        $postMapping = new Mapping(SimplePost::class, '/post/{post}', ['postId']);
        $postMapping->setHiddenProperties(['title', 'body']);

        $mapper = new Mapper();
        $mapper->setClassMap([$postMapping->getClassName() => $postMapping]);

        $jsonApiJsonApiSerializer = new JsonApiTransformer($mapper);

        $expected = <<<JSON
{
    "data": {
        "type": "post",
        "id": "1",
        "attributes": {
            "author_id": 2,
            "comments": [
                {
                    "comment_id": 10,
                    "comment": "I am writing comment no. 1",
                    "user_id": "User 5",
                    "created_at": "2015-07-19T12:48:00+02:00"
                },
                {
                    "comment_id": 20,
                    "comment": "I am writing comment no. 2",
                    "user_id": "User 10",
                    "created_at": "2015-07-20T12:48:00+02:00"
                },
                {
                    "comment_id": 30,
                    "comment": "I am writing comment no. 3",
                    "user_id": "User 15",
                    "created_at": "2015-07-21T12:48:00+02:00"
                },
                {
                    "comment_id": 40,
                    "comment": "I am writing comment no. 4",
                    "user_id": "User 20",
                    "created_at": "2015-07-22T12:48:00+02:00"
                },
                {
                    "comment_id": 50,
                    "comment": "I am writing comment no. 5",
                    "user_id": "User 25",
                    "created_at": "2015-07-23T12:48:00+02:00"
                }
            ], 
            "post_id": 1
        },
        "links": {
            "self": { "href": "/post/1" }
        }
    },
    "links": {
        "self": { "href": "/post/1" }
    },
    "jsonapi": {
        "version": "1.0"
    }
}
JSON;

        $this->assertEquals(
            \json_decode($expected, true),
            \json_decode((new JsonApiSerializer($jsonApiJsonApiSerializer))->serialize($post), true)
        );
    }

    public function testItWillSerializeObjectsNotAddedInMappings()
    {
        $stdClass = new \stdClass();
        $stdClass->userName = 'Joe';
        $stdClass->commentBody = 'Hello World';

        $comment = new SimpleComment(1, $stdClass);
        $mapping = new Mapping(SimpleComment::class, '/comment/{id}', ['id']);

        $mapper = new Mapper();
        $mapper->setClassMap([$mapping->getClassName() => $mapping]);

        $jsonApiJsonApiSerializer = new JsonApiTransformer($mapper);

        $expected = <<<JSON
{
   "data":{
      "type":"comment",
      "id":"1",
      "attributes":{
         "created_at":{
            "date":"2015-11-20 21:43:31.000000",
            "timezone_type":3,
            "timezone":"Europe/Madrid"
         },
         "comment":{
            "user_name":"Joe",
            "comment_body":"Hello World"
         },
         "id": 1
      },
      "links":{
         "self":{
            "href":"/comment/1"
         }
      }
   },
    "links": {
        "self": { "href": "/comment/1" }
    },
   "jsonapi":{
      "version":"1.0"
   }
}
JSON;

        $this->assertEquals(
            \json_decode($expected, true),
            \json_decode((new JsonApiSerializer($jsonApiJsonApiSerializer))->serialize($comment), true)
        );
    }
}
