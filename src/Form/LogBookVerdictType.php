<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class LogBookVerdictType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name')
        ;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $form
            ->add('name')
        ;
    }
    /**
     * @param OptionsResolver $resolver
     * @throws \Symfony\Component\OptionsResolver\Exception\AccessException
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            //'compound' => true,
            // uncomment if you want to bind to a class
            //'data_class' => LogBookVerdict::class,
        ]);
    }

//    public function getParent()
//    {
//        return ChoiceType::class;
//    }
}
