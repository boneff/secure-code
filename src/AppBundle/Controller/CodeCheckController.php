<?php

namespace AppBundle\Controller;

use AppBundle\Entity\CodeCheck;
use AppBundle\Entity\Project;
use SensioLabs\Security\SecurityChecker;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;

/**
 * Codecheck controller.
 *
 * @Route("codecheck")
 */
class CodeCheckController extends Controller
{
    /**
     * Lists all codeCheck entities.
     *
     * @Route("/", name="codecheck_index")
     * @Method("GET")
     */
    public function indexAction()
    {
        $this->denyAccessUnlessGranted('ROLE_USER', null, 'Unable to access this page!');

        $em = $this->getDoctrine()->getManager();

        $codeChecks = $em->getRepository('AppBundle:CodeCheck')->findAll();

        return $this->render('codecheck/index.html.twig', array(
            'codeChecks' => $codeChecks,
        ));
    }

    /**
     * Run code check on project
     *
     * @Route("/run/{$projectId}", name="codecheck_run")
     * @Method("GET")
     */
    public function runAction(Request $request)
    {
        $this->denyAccessUnlessGranted('ROLE_USER', null, 'Unable to access this page!');

        $em = $this->getDoctrine()->getManager();
        /** @var $project Project */
        $project = $em->getRepository('AppBundle:Project')->find($request->get('projectId'));

        try {
            $securityChecker = new SecurityChecker();
            $fileToCheck = $this->getRemoteLockFile($project->getRepositoryUrl());
            $fileToCheck1 = $fileToCheck;
            if (strpos($fileToCheck, 'json') === false) {
                $content = $securityChecker->check($fileToCheck);
            } else {
                // first run composer in given directory if the file is json
                $workDir = str_replace('composer.json', '', $fileToCheck);
                if (chdir($workDir)) {
                    // TODO run composer to generate lock file
//                    $result = exec('echo \'\' | php /usr/bin/composer install --no-interaction');
//                    $fileHandle = fopen($workDir . 'composer.lock');
//                    $fileHandle1 = $fileHandle;
                } else {
                    $content = 'We were unable to perform the check at the moment!';
                }
            }
        } catch (\Exception $e) {
            $statusCode = 400;
            $content = $e->getMessage();
        }

        // curl -H "Accept: application/json" https://security.sensiolabs.org/check_lock -F lock=@/path/to/composer.lock
        if (!empty($content) && is_array($content)) {
            $codeCheck = new Codecheck();
            $codeCheck->setResult(json_encode($content));
            $codeCheck->setIsSecure(false);
            $codeCheck->setDateCreated(new \DateTime());
            $project->addCodeCheck($codeCheck);
            $em->persist($codeCheck);
            $em->flush();
        }

        return $this->render('codecheck/result.html.twig', array(
            'checkResult' => $content ? $content : 'Error' . $statusCode,
        ));
    }

    /**
     * Creates a new codeCheck entity.
     *
     * @Route("/new", name="codecheck_new")
     * @Method({"GET", "POST"})
     */
    public function newAction(Request $request)
    {
        $this->denyAccessUnlessGranted('ROLE_USER', null, 'Unable to access this page!');

        $codeCheck = new Codecheck();
        $form = $this->createForm('AppBundle\Form\CodeCheckType', $codeCheck);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($codeCheck);
            $em->flush();

            return $this->redirectToRoute('codecheck_show', array('id' => $codeCheck->getId()));
        }

        return $this->render('codecheck/new.html.twig', array(
            'codeCheck' => $codeCheck,
            'form' => $form->createView(),
        ));
    }

    /**
     * Finds and displays a codeCheck entity.
     *
     * @Route("/{id}", name="codecheck_show")
     * @Method("GET")
     */
    public function showAction(CodeCheck $codeCheck)
    {
        $this->denyAccessUnlessGranted('ROLE_USER', null, 'Unable to access this page!');

        $deleteForm = $this->createDeleteForm($codeCheck);

        return $this->render('codecheck/show.html.twig', array(
            'codeCheck' => $codeCheck,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing codeCheck entity.
     *
     * @Route("/{id}/edit", name="codecheck_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, CodeCheck $codeCheck)
    {
        $this->denyAccessUnlessGranted('ROLE_USER', null, 'Unable to access this page!');

        $deleteForm = $this->createDeleteForm($codeCheck);
        $editForm = $this->createForm('AppBundle\Form\CodeCheckType', $codeCheck);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('codecheck_index', array('id' => $codeCheck->getId()));
        }

        return $this->render('codecheck/edit.html.twig', array(
            'codeCheck' => $codeCheck,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a codeCheck entity.
     *
     * @Route("/{id}", name="codecheck_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, CodeCheck $codeCheck)
    {
        $this->denyAccessUnlessGranted('ROLE_USER', null, 'Unable to access this page!');

        $form = $this->createDeleteForm($codeCheck);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($codeCheck);
            $em->flush();
        }

        return $this->redirectToRoute('codecheck_index');
    }

    /**
     * Creates a form to delete a codeCheck entity.
     *
     * @param CodeCheck $codeCheck The codeCheck entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(CodeCheck $codeCheck)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('codecheck_delete', array('id' => $codeCheck->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }

    /**
     * @param $url
     * @return string
     */
    private function getRemoteLockFile($url)
    {
        $filePath = '';
        $fileForDownload = 'composer.lock';

        $requestUrl = $this->buildRepoUrl($url, $fileForDownload);
        $file = file_get_contents($requestUrl);

        if ($file == false) {
            $fileForDownload = 'composer.json';
            $requestUrl = $this->buildRepoUrl($url, $fileForDownload);
            $file = file_get_contents($requestUrl);
        }
        $fs = new Filesystem();
        try {
            $randomNum = mt_rand();
            $fs->mkdir('/tmp/'.$randomNum);
            $filePath = '/tmp/' . $randomNum . '/' . $fileForDownload;
            $fs->dumpFile($filePath, $file);
        } catch (IOException $e) {
            echo "An error occurred while creating your directory at ".$e->getPath();
        }

        return $filePath;
    }

    private function buildRepoUrl($url, $fileName)
    {
        //TODO move this logic into separate service
        $hardcodedGithubUrl = 'https://raw.githubusercontent.com';
        $repoName = str_replace('https://github.com/', '', $url);
        $requestUrl = $hardcodedGithubUrl
            . DIRECTORY_SEPARATOR
            . $repoName
            . DIRECTORY_SEPARATOR
            . 'master'
            . DIRECTORY_SEPARATOR
            . $fileName;

        return $requestUrl;
    }
}
