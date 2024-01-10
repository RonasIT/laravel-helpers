<?php

namespace RonasIT\Support\Tests;


use Illuminate\Support\Facades\Mail;
use RonasIT\Support\Tests\Support\Mock\TestMail;

class AssertTraitTest extends HelpersTestCase
{
    public function testMail()
    {
        Mail::to('test@mail.com')->send(new TestMail(
            ['name' => 'John Smith'],
            'emails.test'
        ));

        $this->assertMailEquals(TestMail::class, [
            [
                'emails' => 'text@mail.com',
                'fixture' => 'temp_mail.html',
            ]
        ]);
    }
}