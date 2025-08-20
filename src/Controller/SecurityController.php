<?php

namespace App\Controller;

use App\Entity\InviteUser;
use App\Entity\User;
use App\Form\SignUpFormType;
use App\Services\MessageService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    public function  __construct(private readonly ManagerRegistry $doctrine){}
    #[Route(path: '/login', name: 'login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
         if ($this->getUser()) {
             return $this->redirectToRoute('admin');
         }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
        /*return $this->render('@EasyAdmin/page/login.html.twig', [
            'page_title' => null,
            'csrf_token_intention' => 'authenticate',
            'last_username' => $lastUsername,
            'error' => $error,
            'forgot_password_enabled' => false,
            'remember_me_enabled' => true,
        ]);*/
    }

    #[Route(path: '/logout', name: 'logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }


    #[Route(path: '/signup/{token}', name: 'signup')]
    public function signUp(Request $request, CsrfTokenManagerInterface $csrfTokenManager, $token = null): RedirectResponse|Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('home');
        }

        $em = $this->doctrine->getManager();
        $session = $request->getSession();
        if ($token){
            $token = $em->getRepository(InviteUser::class)->findOneBy(['token' => $token]);
            if ($token){
                $session->set('signupToken', $token->getToken());
            }
            return $this->redirectToRoute('signup');
        }

        $user = new User();
        if ($token = $session->get('signupToken')){
            $token = $em->getRepository(InviteUser::class)->findOneBy(['token' => $token]);
            $user->setEmail($token->getEmail());
            $form = $this->createForm(SignUpFormType::class, $user);
            $form->handleRequest($request);
            if ($form->isSubmitted()) {
                if($form->isValid()) {
                    $user->setFirstName(ucwords(strtolower($form->get('firstName')->getData())));
                    $user->setLastName(ucwords(strtolower($form->get('lastName')->getData())));
                    $user->setEmail(strtolower($form->get('email')->getData()));
                    $user->setPassword($user->encodePassword($form->get('plainPassword')->getData()));
                    $user->setRoles(['ROLE_USER']);
                    $em->persist($user);
                    $em->remove($token);
                    $em->flush();
                    $this->addFlash('success', ($user->isEnabled() ? "." : "Please check your email for the link to activate your account"));
                    $csrfTokenManager->removeToken('signupUser');
                    $session->remove('signupToken');
                    return $this->redirectToRoute('login');
                }
            }
            return $this->render("default/signup.html.twig", [
                'form' => $form->createView(),
            ]);
        }else{
            return $this->render("default/signup.html.twig");
        }
    }

    #[Route(path: '/reset/{token}', name: 'resetPassword')]
    public function resetPassword(Request $request, $token): Response
    {
        $em = $this->doctrine->getManager();
        $user = $em->getRepository(User::class)->findOneBy(['token' => $token]);
        if(!$user){
            $this->addFlash('error', "You need to re-submit your reset request.");
            return $this->redirectToRoute('login');
        }else{
            if(!($user->getTokenDate()->getTimestamp() + 86400 > time())) {
                $user->setToken(null);
                $em->flush();
                $this->addFlash('error', "Sorry, the link has expired.");
                return $this->redirectToRoute('login');
            }else{
                if($request->isMethod('POST')){
                    $password = $request->request->get('password');
                    $confirm_password = $request->request->get('confirm_password');
                    if ($password === $confirm_password) {
                        if(preg_match(User::PASSWORD_POLICY_REGEX, $password)) {
                            $user->setPassword($user->encodePassword($password));
                            $user->setToken(null);
                            $user->setTokenDate(null);
                            $user->setEmailVerified(true);
                            $em->flush();
                            $this->addFlash('success', "Password reset successfully.");
                            return $this->redirectToRoute('login');
                        }else{
                            $this->addFlash('error', User::PASSWORD_POLICY_MESSAGE);
                        }
                    } else {
                        $this->addFlash('error', "The password and confirm password do not match.");
                    }
                }
            }
        }

        return $this->render('security/reset_password.html.twig');
    }

    #[Route(path: '/forgot-password', name: 'forgotPassword')]
    public function forgotPassword(Request $request, MessageService $messageService): Response
    {
        if($request->isMethod('POST')){
            $em = $this->doctrine->getManager();
            $user = $em->getRepository(User::class)->findOneBy(['enabled' => true, 'email' => $request->get('email')]);
            if($user){
                $token = sha1(md5(time()).uniqid().$user->getEmail());
                $user->setToken($token);
                $user->setTokenDate(date_create());
                $em->flush();
                $url = $this->generateUrl('resetPassword', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL);
                $content = "<p>Hare Krsna {$user->getFirstName()},</p>
                            <p>You can reset your password by clicking the link below:</p>
                            <p>{$url}</p>
                            <p>This link is valid for the next 24 hours.</p>";
                $messageService->sendMail($user->getEmail(), 'Reset Password', $content);
                $this->addFlash('success', "An email has been sent. It contains a link you must click to reset your password.");
                return $this->redirectToRoute('login');
            }
            $this->addFlash('error', "Email does not exist.");
            return $this->redirectToRoute('forgotPassword');
        }
        else{
            return $this->render('security/forgot_password.html.twig');
        }
    }

    #[Route(path: '/verify/{token}', name: 'verifyEmail')]
    public function verifyEmail($token, Request $request): RedirectResponse
    {
        $em = $this->doctrine->getManager();
        $user = $em->getRepository(User::class)->findOneBy(['token' => $token]);
        if(!$user){
            $this->addFlash('error', "Invalid link or email is already verified.");
        }else{
            $user->setEmailVerified(true);
            $user->setToken(null);
            $user->setTokenDate(null);
            $user->setEnabled(true);
            $em->flush();
            $this->addFlash('success', "Your email is successfully verified.");
            if($referer = $request->getSession()->get('referer')){
                $request->getSession()->remove('referer');
                return $this->redirect($referer);
            }
        }
        return $this->redirectToRoute('home');
    }
}
