<?php

namespace App\Form;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
class UserType extends AbstractType
{
// In your RegistrationType class
public function buildForm(FormBuilderInterface $builder, array $options)
{
    $builder
        ->add('email')
        ->add('plainPassword', PasswordType::class)
        ->add('roles', ChoiceType::class, [
            'choices' => [
                'University' => 'ROLE_UNIVERSITE',
                'Establishment' => 'ROLE_ETABLISSEMENT',
            ],
            'multiple' => false,
            'expanded' => false,
        ])
        // University fields
        ->add('nomUniversite', TextType::class, [
            'required' => false,
        ])
        // Establishment fields
        ->add('groupe_id', TextType::class, [
            'required' => false,
        ])
        ->add('nom', TextType::class, [
            'required' => false,
        ])
        ->add('etype', TextType::class, [
            'required' => false,
        ])
        ->add('localisation', TextType::class, [
            'required' => false,
        ])
        ->add('logo', FileType::class, [
            'required' => false,
        ])
        ->add('siteweb', UrlType::class, [
            'required' => false,
        ]);
}

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
