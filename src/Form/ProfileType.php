<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $user = $options['data'];

        $builder
            ->add('firstName', null, ['required' => true])
            ->add('lastName', null, ['required' => true])
            ->add('email', EmailType::class, ['disabled' => $user->isEmailVerified()])
            ->add('mobile', TelType::class, ['disabled' => $user->isMobileVerified(), 'attr' => ['maxlength' => '10']])
            ->add('avatarFile', FIleType::class, ['mapped' => false, 'label' => false, 'required' => false])
            ->add('removeAvatar', CheckboxType::class, [
                'mapped' => false,
                'required' => false
            ])
            ->add('dateOfBirth', DateType::class, [
                'required' => false,
                'widget' => 'single_text',
                //'input'  => 'datetime_immutable'
            ])
            ->add('gender', ChoiceType::class,[
                'choices' => [
                    'Select Gender' => '',
                    'Male' => 1,
                    'Female' => 2,
                ]
            ])
            ->add('company')
            ->add('pan')
            ->add('gstNumber')
            ->add('additionalEmail', EmailType::class, ['required' => false, 'attr' => ['multiple' => true]])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
