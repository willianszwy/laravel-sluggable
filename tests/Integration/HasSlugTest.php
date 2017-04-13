<?php

namespace Spatie\Sluggable\Test\Integration;

use Spatie\Sluggable\SlugOptions;

class HasSlugTest extends TestCase
{
    /** @test */
    public function it_will_save_a_slug_when_saving_a_model()
    {
        $model = TestModel::create(['name' => 'this is a test']);

        $this->assertEquals('this-is-a-test', $model->url);
    }

    /** @test */
    public function it_can_handle_null_values_when_creating_slugs()
    {
        $model = TestModel::create(['name' => null]);

        $this->assertEquals('-1', $model->url);
    }

    /** @test */
    public function it_will_not_change_the_slug_when_the_source_field_is_not_changed()
    {
        $model = TestModel::create(['name' => 'this is a test']);

        $model->other_field = 'otherValue';
        $model->save();

        $this->assertEquals('this-is-a-test', $model->url);
    }

    /** @test */
    public function it_will_update_the_slug_when_the_source_field_is_changed()
    {
        $model = TestModel::create(['name' => 'this is a test']);

        $model->name = 'this is another test';
        $model->save();

        $this->assertEquals('this-is-another-test', $model->url);
    }

    /** @test */
    public function it_will_save_a_unique_slug_by_default()
    {
        TestModel::create(['name' => 'this is a test']);

        foreach (range(1, 10) as $i) {
            $model = TestModel::create(['name' => 'this is a test']);
            $this->assertEquals("this-is-a-test-{$i}", $model->url);
        }
    }

    /** @test */
    public function it_can_handle_empty_source_fields()
    {
        foreach (range(1, 10) as $i) {
            $model = TestModel::create(['name' => '']);
            $this->assertEquals("-{$i}", $model->url);
        }
    }

    /** @test */
    public function it_can_generate_slugs_from_multiple_source_fields()
    {
        $model = new class extends TestModel {
            public function getSlugOptions(): SlugOptions
            {
                return parent::getSlugOptions()->generateSlugsFrom(['name', 'other_field']);
            }
        };

        $model->name = 'this is a test';
        $model->other_field = 'this is another field';
        $model->save();

        $this->assertEquals('this-is-a-test-this-is-another-field', $model->url);
    }

    /** @test */
    public function it_can_generate_slugs_from_a_callable()
    {
        $model = new class extends TestModel {
            public function getSlugOptions(): SlugOptions
            {
                return parent::getSlugOptions()->generateSlugsFrom(function (TestModel $model): string {
                    return 'foo-'.str_slug($model->name);
                });
            }
        };

        $model->name = 'this is a test';
        $model->save();

        $this->assertEquals('foo-this-is-a-test', $model->url);
    }

    /** @test */
    public function it_can_generate_duplicate_slugs()
    {
        foreach (range(1, 10) as $i) {
            $model = new class extends TestModel {
                public function getSlugOptions(): SlugOptions
                {
                    return parent::getSlugOptions()->allowDuplicateSlugs();
                }
            };

            $model->name = 'this is a test';
            $model->save();

            $this->assertEquals('this-is-a-test', $model->url);
        }
    }

    /** @test */
    public function it_can_generate_slugs_with_a_maximum_length()
    {
        $model = new class extends TestModel {
            public function getSlugOptions(): SlugOptions
            {
                return parent::getSlugOptions()->slugsShouldBeNoLongerThan(5);
            }
        };

        $model->name = '123456789';
        $model->save();

        $this->assertEquals('12345', $model->url);
    }

    /**
     * @test
     * @dataProvider weirdCharacterProvider
     */
    public function it_can_handle_weird_characters_when_generating_the_slug(string $weirdCharacter, string $normalCharacter)
    {
        $model = TestModel::create(['name' => $weirdCharacter]);

        $this->assertEquals($normalCharacter, $model->url);
    }

    public function weirdCharacterProvider()
    {
        return [
            ['é', 'e'],
            ['è', 'e'],
            ['à', 'a'],
            ['a€', 'a'],
            ['ß', 'ss'],
            ['a/ ', 'a'],
        ];
    }

    /** @test */
    public function it_can_handle_overwrites_when_updating_a_model()
    {
        $model = TestModel::create(['name' => 'this is a test']);

        $model->url = 'this-is-an-url';
        $model->save();

        $this->assertEquals('this-is-an-url', $model->url);
    }

    /** @test */
    public function it_can_handle_duplicates_when_overwriting_a_slug()
    {
        $model = TestModel::create(['name' => 'this is a test']);
        $other_model = TestModel::create(['name' => 'this is an other']);

        $model->url = 'this-is-an-other';
        $model->save();

        $this->assertEquals('this-is-an-other-1', $model->url);
    }

    /** @test */
    public function it_has_an_method_that_prevents_a_slug_being_generated_on_creation()
    {
        $model = new class extends TestModel {
            public function getSlugOptions(): SlugOptions
            {
                return parent::getSlugOptions()->doNotGenerateSlugsOnCreate();
            }
        };

        $model->name = 'this is a test';
        $model->save();

        $this->assertEquals(null, $model->url);
    }

    /** @test */
    public function it_has_an_method_that_prevents_a_slug_being_generated_on_update()
    {
        $model = new class extends TestModel {
            public function getSlugOptions(): SlugOptions
            {
                return parent::getSlugOptions()->doNotGenerateSlugsOnUpdate();
            }
        };

        $model->name = 'this is a test';
        $model->save();

        $model->name = 'this is another test';
        $model->save();

        $this->assertEquals('this-is-a-test', $model->url);
    }

    /** @test */
    public function it_can_accept_other_separator()
    {
        $model = new class extends TestModel {
            public function getSlugOptions(): SlugOptions
            {
                return parent::getSlugOptions()->withSeparator('_');
            }
        };

        $model->name = 'this is a test';
        $model->save();

        $this->assertEquals('this_is_a_test', $model->url);
    }

    /** @test */
    public function it_can_accept_other_separator_in_a_uniq_slug()
    {
        $model = new class extends TestModel {
            public function getSlugOptions(): SlugOptions
            {
                return parent::getSlugOptions()->withSeparator('_');
            }
        };

        $model->name = 'this is a test';
        $model->save();

        foreach (range(1, 10) as $i) {
            $other_model = new class extends TestModel {
                public function getSlugOptions(): SlugOptions
                {
                    return parent::getSlugOptions()->withSeparator('_');
                }
            };

            $other_model->name = 'this is a test';
            $other_model->save();

            $this->assertEquals("this_is_a_test_{$i}", $other_model->url);
        }
    }

    /** @test */
    public function it_can_handle_null_values_when_creating_slugs_with_othe_separator()
    {
        $model = new class extends TestModel {
            public function getSlugOptions(): SlugOptions
            {
                return parent::getSlugOptions()->withSeparator('_');
            }
        };

        $model->name = null;
        $model->save();

        $this->assertEquals('_1', $model->url);
    }
}
