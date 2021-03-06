<?php

namespace App\Controller;

use App\Entity\Task;
use App\Form\TaskType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

class TaskController extends AbstractController // Permet d'utiliser la méthode render
{
    /**
     * @Route("/tasks", name="task_list")
     */
    public function listAction()
    {
        return $this->render('task/list.html.twig', [
            'tasks' => $this->getDoctrine()->getRepository(Task::class)->findBy(
                ['isDone' => false],
                ['title' => 'ASC']
            )
        ]);
    }

    /**
     * @Route("/tasks/finish", name="task_finish_list")
     */
    public function listFinishAction()
    {
        return $this->render('task/finish.html.twig', [
            'tasks' => $this->getDoctrine()->getRepository(Task::class)->findBy(
                ['isDone' => true],
                ['title' => 'ASC']
            )
        ]);
    }

    /**
     * @Route("/tasks/create", name="task_create")
     * @param Request $request
     * @return RedirectResponse|Response
     */
    public function createAction(Request $request)
    {
        $task = new Task();
        $form = $this->createForm(TaskType::class, $task);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            // L'utilisateur est rattaché à la tâche
            $task->setUser($this->getUser());

            $em->persist($task);
            $em->flush();

            $this->addFlash('success', 'La tâche a été bien été ajoutée.');

            return $this->redirectToRoute('task_list');
        }

        return $this->render('task/create.html.twig', ['form' => $form->createView()]);
    }

    /**
     * @Route("/tasks/{id}/edit", name="task_edit")
     * @param $id
     * @param Request $request
     * @return RedirectResponse|Response
     */
    public function editAction($id, Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        // Récupère la tâche
        $task = $em->getRepository(Task::class)->find($id);

        $form = $this->createForm(TaskType::class, $task);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            $this->addFlash('success', 'La tâche a bien été modifiée.');

            return $this->redirectToRoute('task_list');
        }

        return $this->render('task/edit.html.twig', [
            'form' => $form->createView(),
            'task' => $task,
        ]);
    }

    /**
     * @Route("/tasks/{id}/toggle", name="task_toggle")
     * @param $id
     * @return RedirectResponse
     */
    public function toggleTaskAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        // Récupère la tâche
        $task = $em->getRepository(Task::class)->find($id);

        $task->toggle(!$task->isDone());
        $this->getDoctrine()->getManager()->flush();

        // Message de rétroaction différent selon le nouvel indicateur de tâche
        $feedback = $task->isDone() ? 'La tâche "%s" est terminée.' : 'La tâche "%s" est en cours.';
        $this->addFlash('success', sprintf($feedback, $task->getTitle()));

        return $this->redirectToRoute('task_list');
    }

    /**
     * @Route("/tasks/{id}/delete", name="task_delete")
     * @param $id
     * @return RedirectResponse
     */
    public function deleteTaskAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        // Récupère la tâche
        $task = $em->getRepository(Task::class)->find($id);

        // Récupère un tableau avec les rôles de l'utilisateur
        $arrayRoles = $this->getUser()->getRoles();

            // Si la tâche est anonyme
        if ($task->getUser()->getUsername() == 'Anonyme') {
            // Si l'utilisateur n'est pas un ADMIN
            if ($this->getUser()->getRoles() != in_array('ROLE_ADMIN', $arrayRoles)) {
                $this->addFlash('error', 'Seul un admin peut supprimer une tâche de l\'utilisateur anonyme');
                return $this->redirectToRoute('task_list');
            }
        }

        // Si la tâche n'est pas anonyme
        if ($task->getUser()->getUsername() != 'Anonyme') {
            // Si l'utilisateur n'est pas celui qui a créé la tâche
            if ($this->getUser() != $task->getUser()) {
                $this->addFlash('error', 'Seul l\'auteur de la tâche peut la supprimer');
                return $this->redirectToRoute('task_list');
            }
        }

        $em->remove($task);
        $em->flush();

        $this->addFlash('success', 'La tâche a bien été supprimée.');

        return $this->redirectToRoute('task_list');
    }
}
