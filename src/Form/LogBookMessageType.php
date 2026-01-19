<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class LogBookMessageType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('msgType')
            ->add('chain')
            //->add('test')
            ->add('logTime')
            ->add('message',TextareaType::class, array(
                'attr' => array('class' => 'form-control', 'rows' => '25'),
            ))
        ;
    }
    /**
     * @param OptionsResolver $resolver
     * @throws \Symfony\Component\OptionsResolver\Exception\AccessException
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // uncomment if you want to bind to a class
            //'data_class' => LogBookMessage::class,
        ]);
    }
}
