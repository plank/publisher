<?php

use function Pest\Laravel\get;

it('correctly forces draft content on the configured draft_paths', function () {
get('pages/1')->assertSee('Draft Content Restricted');
    get('admin')->assertSee('Draft Content Visible');
    get('admin/dashboard')->assertSee('Draft Content Visible');
    get('admin/resources/pages/1/details')->assertSee('Draft Content Visible');
    });
