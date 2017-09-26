<?php
use PHPUnit\Framework\TestCase;
use IceAge\RouteGroup;
use IceAge\Application;

class IceAge_RouteGroup_Test extends TestCase {
    public function testBasicGroup(){

        $group = new RouteGroup('/admin');

        $group->get('/photo', function(){
            return 'PhotoAdmin';
        });

        $group->post('/gallery', function(){
            return 'GalleryAdmin';
        });

        $this->assertCount(3, $group->match('/admin/photo', 'GET'));
        $this->assertCount(3, $group->match('/admin/gallery', 'POST'));
        $this->assertCount(0, $group->match('/admin/photo', 'POST'));
        $this->assertCount(0, $group->match('/admin/gallery', 'GET'));
        $this->assertCount(0, $group->match('/admin/somethingelse', 'GET'));
    }
}