<?php

namespace App\Form;

use App\Entity\LogBookUser;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LogBookUserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('email', EmailType::class)
            ->add('username', TextType::class)
            ->add('plainPassword', RepeatedType::class, array(
                'type' => PasswordType::class,
                'first_options'  => array('label' => 'Password'),
                'second_options' => array('label' => 'Repeat Password'),
            ))
        ;
        if(in_array("edit_enabled", $options) && $options["edit_enabled"] === true){
            $builder
                ->add('roles', ChoiceType::class, [
                    'multiple' => true,
                    'expanded' => true, // render check-boxes
                    'choices' => [
                        'Super Admin' => 'ROLE_SUPER_ADMIN',
                        'Admin' => 'ROLE_ADMIN',
                        'Manager' => 'ROLE_MANAGER',
                        'USER' => 'ROLE_USER',
                        // ...
                    ],
                ])
            ;
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(array(
            'edit_enabled'
        ));
        
        $resolver->setDefaults(array(
            'data_class' => LogBookUser::class,
            'edit_enabled' => false,
        ));
    }

}
