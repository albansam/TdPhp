<?php
use Gregwar\Image\Image;
/* Home rendering*/
$app->match('/', function() use ($app) {
    return $app['twig']->render('home.html.twig');
})->bind('home');
/* Books list rendering */
$app->match('/books', function() use ($app) {
    return $app['twig']->render('books.html.twig', array(
        'books' => $app['model']->getBooks()
    ));
})->bind('books');
/* Book details rendering */
$app->match('/detailsBooks', function() use ($app) {
    return $app['twig']->render('detailsBooks.html.twig',array(
        'detailsBooks' => $app['model']->getDetailsBooks($_GET['bookId']),
        'copiesNumber' => $app['model']->getCopiesNumber($_GET['bookId']),
        'bookCopies' => $app['model']->getBookCopies($_GET['bookId']),
        'holdCopies' => $app['model']->getHoldNumber($_GET['bookId'])
    ));
})->bind('detailsBooks');
/* New borrow */
$app->match('/borrow', function() use ($app) {
    $request = $app['request'];
    if ($request->getMethod() == 'POST') {
        $post = $request->request;
        if($post->has('bookHolder') and $post->get('bookHolder') != '' and $post->has('returnDate') and $post->get('returnDate') != ''){
            if($app['model']->checkIfEmpruntExists($post->get('copyId'))){
                return $app['twig']->render('borrow.html.twig',array(
                    'empruntId' => ($_GET['copyId']),
                    'sendEmprunt' => '1',
                    'successEmprunt' => '0'
                ));
            }
            else{
                $app['model']->setNewEmprunt($post->get('copyId'),$post->get('bookHolder'),$post->get('returnDate'));
                return $app['twig']->render('borrow.html.twig',array(
                    'empruntId' => ($_GET['copyId']),
                    'sendEmprunt' => '1',
                    'successEmprunt' => '1'
                ));
            }
        }
        else{
            return $app['twig']->render('borrow.html.twig',array(
                'empruntId' => ($_GET['copyId']),
                'sendEmprunt' => '1',
                'successEmprunt' => '0'
            ));
        }
    }
    else{
        return $app['twig']->render('borrow.html.twig',array(
            'empruntId' => ($_GET['copyId'])
        ));
    }
})->bind('borrow');
/* Terminate borrow */
$app->match('/endBorrow', function() use ($app) {
    $request = $app['request'];
    if ($request->getMethod() == 'POST') {
        $post = $request->request;
        if($post->has('returnDate') and $post->get('returnDate') != ''){
            $app['model']->returnEmprunt($_GET['copyId'],$post->get('returnDate'));
            return $app['twig']->render('endBorrow.html.twig',array(
                'empruntId' => ($_GET['copyId']),
                'returnDone' => '1'
            ));
        }
        else{
            return $app['twig']->render('endBorrow.html.twig',array(
                'empruntId' => ($_GET['copyId']),
                'returnDone' => '0'
            ));
        }
    }
    else{
        return $app['twig']->render('endBorrow.html.twig',array(
            'empruntId' => ($_GET['copyId'])
        ));
    }
})->bind('endBorrow');
/* Authentication system */
$app->match('/admin', function() use ($app) {
    $request = $app['request'];
    $success = false;
    if ($request->getMethod() == 'POST') {
        $post = $request->request;
        if ($post->has('login') && $post->has('password')){
            $found = false;
            foreach ($app['config']['admin'] as $authType){
                if((array($post->get('login'), $post->get('password')) == $authType) && !$found){
                    $app['session']->set('admin', true);
                    $success = true;
                    $found = true;
                }
            }
        }
    }
    return $app['twig']->render('admin.html.twig', array(
        'success' => $success
    ));
})->bind('admin');
/* Disconnection */
$app->match('/logout', function() use ($app) {
    $app['session']->remove('admin');
    return $app->redirect($app['url_generator']->generate('admin'));
})->bind('logout');
/* Adding a book */
$app->match('/addBook', function() use ($app) {
    if (!$app['session']->has('admin')) {
        return $app['twig']->render('shouldBeAdmin.html.twig');
    }
    $request = $app['request'];
    if ($request->getMethod() == 'POST') {
        $post = $request->request;
        if ($post->has('title') && $post->has('author') && $post->has('synopsis') &&
            $post->has('copies')) {
            $files = $request->files;
            $image = '';
            // Resizing image
            if ($files->has('image') && $files->get('image')) {
                $image = sha1(mt_rand().time());
                Image::open($files->get('image')->getPathName())
                    ->resize(240, 300)
                    ->save('uploads/'.$image.'.jpg');
                Image::open($files->get('image')->getPathName())
                    ->resize(120, 150)
                    ->save('uploads/'.$image.'_small.jpg');
            }
            // Saving the book to database
            $app['model']->insertBook($post->get('title'), $post->get('author'), $post->get('synopsis'),
                $image, (int)$post->get('copies'));
        }
    }
    return $app['twig']->render('addBook.html.twig');
})->bind('addBook');
