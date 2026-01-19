<?php

namespace App\Form;

use App\Entity\LogBookTestFailDesc;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LogBookTestFailDescType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('description')
            ->add('md5')
            ->add('createdAt')
            ->add('testsCount')
            ->add('lastMarkedAsSeenAt')
            ->add('lastUpdateDiff')
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => LogBookTestFailDesc::class,
        ]);
    }
}
