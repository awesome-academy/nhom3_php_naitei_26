<?php

namespace Tests\Feature;

use Tests\TestCase;

class CitizenSpaTest extends TestCase
{
    public function test_citizen_root_renders_the_react_mount_point(): void
    {
        $response = $this->get('/');

        $response
            ->assertOk()
            ->assertViewIs('citizen.app')
            ->assertSee('emblem-vietnam.svg')
            ->assertSee('id="citizen-app"', false);
    }

    public function test_citizen_client_side_route_renders_the_same_spa(): void
    {
        $response = $this->get('/applications');

        $response
            ->assertOk()
            ->assertViewIs('citizen.app');
    }

    public function test_citizen_apply_route_renders_the_same_spa(): void
    {
        $response = $this->get('/services/1/apply');

        $response
            ->assertOk()
            ->assertViewIs('citizen.app');
    }

    public function test_citizen_application_detail_route_renders_the_same_spa(): void
    {
        $response = $this->get('/applications/1');

        $response
            ->assertOk()
            ->assertViewIs('citizen.app')
            ->assertSee('id="citizen-app"', false);
    }

    public function test_citizen_auth_routes_render_the_same_spa(): void
    {
        foreach (['/login', '/register', '/profile'] as $path) {
            $this->get($path)
                ->assertOk()
                ->assertViewIs('citizen.app');
        }
    }
}
