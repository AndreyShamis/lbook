<?php

namespace App\Form;

use App\Entity\TestEventCmu;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TestEventCmuType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('block')
            ->add('fault')
            ->add('a_value')
            ->add('b_value')
            ->add('a_time')
            ->add('b_time')
            ->add('createdAt')
            ->add('test')
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => TestEventCmu::class,
        ]);
    }
}
