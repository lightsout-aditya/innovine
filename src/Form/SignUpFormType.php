<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SignUpFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', null, ['required' => true])
            ->add('lastName', null, ['required' => true])
            ->add('email', EmailType::class, ['required' => true])
            ->add('mobile', TelType::class, ['required' => true])
            ->add('plainPassword', PasswordType::class, ['required' => true, 'attr' => ['pattern' => false]])
            ->add('agree', CheckboxType::class, ['required' => true, 'mapped' => false])
            ->add('captcha', ReCaptchaType::class, [
                'type' => 'checkbox', // (invisible, checkbox)
                'mapped' => false
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id'   => 'signupUser'
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'signup';
    }

    public function getName(): string
    {
        return $this->getBlockPrefix();
    }
}
