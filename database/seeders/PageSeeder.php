<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Page::create([
            'title' => 'About Us',
            'description' => '<div class="markdown">
<h3>About Us</h3>
<p>This is a legal agreement between you, the Purchaser, and Tabler. Purchasing or downloading of any Tabler product (Tabler Free, Tabler PRO, Tabler Email), constitutes your acceptance of the terms of this license, <a href="https://tabler.io/terms-of-service.html">Tabler terms of service</a> and <a href="https://tabler.io/privacy-policy.html">Tabler private policy</a>.</p>
<p>A license grants you a non-exclusive and non-transferable right to use and incorporate the item in your personal or commercial projects.</p>
<h3 id="tabler-free-license">Tabler Free License</h3>
<p>Tabler Free is available under MIT License</p>
<p>Copyright 2023 Tabler</p>
<p>Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the &ldquo;Software&rdquo;), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:</p>
<p>The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.</p>
<p>THE SOFTWARE IS PROVIDED &ldquo;AS IS&rdquo;, WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.</p>
<h3 id="tabler-pro-and-tabler-email-license">Tabler PRO and Tabler Email License</h3>
<p>After Purchasing you are granted the use products under the conditions featured belowed.</p>
<p>Rights</p>
<ol>
<li>You have rights to use our resources for any or all of your personal and commercial projects.</li>
<li>You may modify the resources according to your requirements.</li>
<li>You are not required to attribute or link to Tabler in any of your projects.</li>
</ol>
<p>Restrictions</p>
<ol>
<li>You do not have the rights to redistribute, resell, lease, license, sub-license or offer the file downloaded to any third party.</li>
<li>For any resalable web applications or software programs, you cannot include our graphic resources as an additional attachment.</li>
<li>You cannot redistribute any of the software, or products created with Tabler paid products.</li>
<li>You cannot add our source code to any open source repository.</li>
<li>The source code may not be placed on any website in a complete or archived downloadable format.</li>
</ol>
</div>',
            'views' => '0',
        ]);

        Page::create([
            'title' => 'Contact Us',
            'description' => '<div class="markdown">Test Page Contact Us</div>',
            'views' => '0',
        ]);

        Page::create([
            'title' => 'FAQ',
            'description' => '<div>
            <h2 class="mb-3">1. Introduction</h2>
            <div id="faq-1" class="accordion" role="tablist" aria-multiselectable="true">
            <div class="accordion-item">
            <div class="accordion-header" role="tab"><button class="accordion-button" data-bs-toggle="collapse" data-bs-target="#faq-1-1">Welcome to our service!</button></div>
            <div id="faq-1-1" class="accordion-collapse collapse show" role="tabpanel" data-bs-parent="#faq-1">
            <div class="accordion-body pt-0">
            <div>
            <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Accusantium alias dignissimos dolorum ea est eveniet, excepturi illum in iste iure maiores nemo recusandae rerum saepe sed, sunt totam! Explicabo, ipsa?</p>
            <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Accusantium alias dignissimos dolorum ea est eveniet, excepturi illum in iste iure maiores nemo recusandae rerum saepe sed, sunt totam! Explicabo, ipsa?</p>
            </div>
            </div>
            </div>
            </div>
            <div class="accordion-item">
            <div class="accordion-header" role="tab"><button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#faq-1-2">Who are we?</button></div>
            <div id="faq-1-2" class="accordion-collapse collapse" role="tabpanel" data-bs-parent="#faq-1">
            <div class="accordion-body pt-0">
            <div>
            <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Accusantium alias dignissimos dolorum ea est eveniet, excepturi illum in iste iure maiores nemo recusandae rerum saepe sed, sunt totam! Explicabo, ipsa?</p>
            <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Accusantium alias dignissimos dolorum ea est eveniet, excepturi illum in iste iure maiores nemo recusandae rerum saepe sed, sunt totam! Explicabo, ipsa?</p>
            </div>
            </div>
            </div>
            </div>
            <div class="accordion-item">
            <div class="accordion-header" role="tab"><button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#faq-1-3">What are our values?</button></div>
            <div id="faq-1-3" class="accordion-collapse collapse" role="tabpanel" data-bs-parent="#faq-1">
            <div class="accordion-body pt-0">
            <div>
            <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Accusantium alias dignissimos dolorum ea est eveniet, excepturi illum in iste iure maiores nemo recusandae rerum saepe sed, sunt totam! Explicabo, ipsa?</p>
            <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Accusantium alias dignissimos dolorum ea est eveniet, excepturi illum in iste iure maiores nemo recusandae rerum saepe sed, sunt totam! Explicabo, ipsa?</p>
            </div>
            </div>
            </div>
            </div>
            </div>
            </div>
            <div>
            <h2 class="mb-3">2. Functionality</h2>
            <div id="faq-2" class="accordion" role="tablist" aria-multiselectable="true">
            <div class="accordion-item">
            <div class="accordion-header" role="tab"><button class="accordion-button" data-bs-toggle="collapse" data-bs-target="#faq-2-1">What are the key features?</button></div>
            <div id="faq-2-1" class="accordion-collapse collapse show" role="tabpanel" data-bs-parent="#faq-2">
            <div class="accordion-body pt-0">
            <div>
            <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Accusantium alias dignissimos dolorum ea est eveniet, excepturi illum in iste iure maiores nemo recusandae rerum saepe sed, sunt totam! Explicabo, ipsa?</p>
            <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Accusantium alias dignissimos dolorum ea est eveniet, excepturi illum in iste iure maiores nemo recusandae rerum saepe sed, sunt totam! Explicabo, ipsa?</p>
            </div>
            </div>
            </div>
            </div>
            <div class="accordion-item">
            <div class="accordion-header" role="tab"><button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#faq-2-2">Does your App support mobile devices?</button></div>
            <div id="faq-2-2" class="accordion-collapse collapse" role="tabpanel" data-bs-parent="#faq-2">
            <div class="accordion-body pt-0">
            <div>
            <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Accusantium alias dignissimos dolorum ea est eveniet, excepturi illum in iste iure maiores nemo recusandae rerum saepe sed, sunt totam! Explicabo, ipsa?</p>
            <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Accusantium alias dignissimos dolorum ea est eveniet, excepturi illum in iste iure maiores nemo recusandae rerum saepe sed, sunt totam! Explicabo, ipsa?</p>
            </div>
            </div>
            </div>
            </div>
            <div class="accordion-item">
            <div class="accordion-header" role="tab"><button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#faq-2-3">Why should I choose your service?</button></div>
            <div id="faq-2-3" class="accordion-collapse collapse" role="tabpanel" data-bs-parent="#faq-2">
            <div class="accordion-body pt-0">
            <div>
            <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Accusantium alias dignissimos dolorum ea est eveniet, excepturi illum in iste iure maiores nemo recusandae rerum saepe sed, sunt totam! Explicabo, ipsa?</p>
            <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Accusantium alias dignissimos dolorum ea est eveniet, excepturi illum in iste iure maiores nemo recusandae rerum saepe sed, sunt totam! Explicabo, ipsa?</p>
            </div>
            </div>
            </div>
            </div>
            <div class="accordion-item">
            <div class="accordion-header" role="tab"><button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#faq-2-4">Is my data secure?</button></div>
            <div id="faq-2-4" class="accordion-collapse collapse" role="tabpanel" data-bs-parent="#faq-2">
            <div class="accordion-body pt-0">
            <div>
            <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Accusantium alias dignissimos dolorum ea est eveniet, excepturi illum in iste iure maiores nemo recusandae rerum saepe sed, sunt totam! Explicabo, ipsa?</p>
            <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Accusantium alias dignissimos dolorum ea est eveniet, excepturi illum in iste iure maiores nemo recusandae rerum saepe sed, sunt totam! Explicabo, ipsa?</p>
            </div>
            </div>
            </div>
            </div>
            </div>
            </div>',
            'views' => '0',
        ]);
    }
}
