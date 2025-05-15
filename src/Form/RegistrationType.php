<?php 
namespace App\Form;

use App\Entity\User;
use App\Entity\Etablissement;
use App\Entity\Universite;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
class RegistrationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class)
            ->add('plainPassword', PasswordType::class, [
                'mapped' => false,
            ])
            ->add('roles', ChoiceType::class, [
                'choices' => [
                    'University' => 'ROLE_UNIVERSITE',
                    'Establishment' => 'ROLE_ETABLISSEMENT',
                ],
                'multiple' => false,
                'expanded' => false,
                'mapped' => false,
            ])
            // University specific fields
            ->add('nomUniversite', TextType::class, [
                'required' => false,
                'mapped' => false,
                'label' => 'University Name',
            ])
            // Establishment specific fields
            ->add('nomEtablissement', TextType::class, [
                'required' => false,
                'mapped' => false,
                'label' => 'Establishment Name',
            ])
            ->add('etype', ChoiceType::class, [
                'required' => false,
                'mapped' => false,
                'choices' => array_combine(Etablissement::ETYPES, Etablissement::ETYPES),
                'label' => 'Type',
            ])
            ->add('localisation', TextType::class, [
                'required' => false,
                'mapped' => false,
                'label' => 'Location',
            ])
            ->add('logo', FileType::class, [
                'required' => false,
                'mapped' => false,
            ])
            ->add('siteweb', UrlType::class, [
                'required' => false,
                'mapped' => false,
                'label' => 'Website',
            ])
            ->add('universite', EntityType::class, [
                'class' => Universite::class,
                'choice_label' => 'nom',
                'required' => false,
                'mapped' => false,
                'label' => 'Associated University',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}