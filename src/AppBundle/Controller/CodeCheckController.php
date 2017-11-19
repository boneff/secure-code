<?php

namespace AppBundle\Controller;

use AppBundle\Entity\CodeCheck;
use AppBundle\Entity\Project;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;

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
    public function runAction(Request $request, KernelInterface $kernel)
    {
        $this->denyAccessUnlessGranted('ROLE_USER', null, 'Unable to access this page!');

        $em = $this->getDoctrine()->getManager();

        $application = new Application($kernel);
        $project = $em->getRepository('AppBundle:Project')->find($request->get('projectId'));
        $application->setAutoExit(false);

        $input = new ArrayInput([
            'command'  => 'security:check',
            'lockfile' => $this->getRemoteLockFile($project->getRepositoryUrl())
        ]);

        // You can use NullOutput() if you don't need the output
        $output = new BufferedOutput();
        $application->run($input, $output);

        // return the output, don't use if you used NullOutput()
        $content = $output->fetch();
        // $codeCheck = new Codecheck();

        return $this->render('codecheck/result.html.twig', array(
            'checkResult' => $content,
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

            return $this->redirectToRoute('codecheck_edit', array('id' => $codeCheck->getId()));
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
        try {
            $file = file_get_contents($url
                . DIRECTORY_SEPARATOR
                . 'blob'
                . DIRECTORY_SEPARATOR
                . 'master'
                . DIRECTORY_SEPARATOR
                . 'composer.lock');
        } catch (Exception $e) {

        }

        $fs = new Filesystem();
        try {
            $randomNum = mt_rand();
            $fs->mkdir('/tmp/'.$randomNum);
            $filePath = '/tmp/' . $randomNum . '/composer.lock';
            $fs->dumpFile($filePath, $file);
        } catch (IOException $e) {
            echo "An error occurred while creating your directory at ".$e->getPath();
        }

        return $filePath;
    }
}
