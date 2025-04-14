<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Psr\Log\LoggerInterface; 
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;


class ResetPasswordController extends AbstractController
{
    #[Route('/forgot-password', name: 'forgot_password')]
    public function forgotPassword(): Response
    {
        return $this->render('reset_password/forgot_password.html.twig');
    }

    #[Route('/send-otp', name: 'send_otp')]
    public function sendOtp(Request $request, EntityManagerInterface $em, MailerInterface $mailer): Response
    {
        $email = $request->request->get('email');
        $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);

        if (!$user) {
            $this->addFlash('error', 'Email not found.');
            return $this->redirectToRoute('forgot_password');
        }

        // Generate a 6-digit OTP
        $otp = random_int(100000, 999999);
        $user->setOtp($otp);
        $em->flush();

        // Create the email
        $emailMessage = (new Email())
            ->from('arafetksiksi7@gmail.com') // Your Gmail
            ->to($email)
            ->subject('Your OTP Code')
            ->text("Your OTP code is: $otp")
            ->html("<p>Your OTP code is: <strong>$otp</strong></p>");

        // Send the email
        $mailer->send($emailMessage);

        $this->addFlash('success', 'OTP sent successfully.');
        return $this->render('reset_password/enter_otp.html.twig', ['email' => $email]);
    }
    #[Route('/verify-otp', name: 'verify_otp', methods: ['POST'])]
    public function verifyOtp(Request $request, EntityManagerInterface $em): Response
    {
        $email = $request->request->get('email');
        $otp = $request->request->get('otp');
        $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);

        if (!$user || $user->getOtp() !== $otp) {
            $this->addFlash('error', 'Invalid OTP.');
            return $this->render('reset_password/enter_otp.html.twig', ['email' => $email]);
        }

        $user->setOtp(null); // Clear OTP
        $em->flush();

        return $this->render('reset_password/reset_password.html.twig', ['email' => $email]);
    }



    #[Route('/reset-password', name: 'reset_password', methods: ['GET', 'POST'])]
        public function resetPassword(
            
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        LoggerInterface $logger // Inject the logger
       
    ): Response {
        $email = $request->request->get('email');
        $password = $request->request->get('password');
    
        // Log the received email and password
        $logger->info('ResetController: Reset password request received.', [
            'email' => $email,
            'password' => $password,
        ]);
    
        if (!$email) {
            $logger->error('ResetController: Email is required.');
            $this->addFlash('error', 'Email is required.');
            return $this->render('reset_password/reset_password.html.twig');
        }
    
        $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);
        if (!$user) {
            $logger->error('ResetController: User not found.', ['email' => $email]);
            $this->addFlash('error', 'User not found.');
            return $this->render('reset_password/reset_password.html.twig', ['email' => $email]);
        }
    
        // Log the user found
        $logger->info('ResetController: User found.', ['user_id' => $user->getId(), 'email' => $user->getEmail()]);
    
        // Hash the new password
        $hashedPassword = $passwordHasher->hashPassword($user, $password);
        $logger->info('ResetController: Password hashed.', ['hashed_password' => $hashedPassword]);
    
        // Set the new password
        $user->setPassword($hashedPassword);
        $logger->info('ResetController: Password set on user object.');
    
        // Save the updated user to the database
        $em->flush();

    
        return $this->redirectToRoute('login');
    }
}
